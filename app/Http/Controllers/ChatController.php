<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\ChatParticipant;
use App\Models\Chatroom;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * ChatController
 *
 * A chat consist of a message between two users with different roles
 * Sellers can chat to users and users can chat to sellers
 * Admin can chat to all users
 *
 */
class ChatController extends Controller
{
    /**
     * Display a listing of the chatrooms.
     *
     * Used to find recipients for chat
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = $request->user();
        // Validate fields from request where as is required and within the string "member", "seller" or "admin"
        // and store_id is required if as is seller
        $this->validate($request, [
            'as' => 'string|required|in:member,seller,admin',
            'store_id' => 'exists:stores,id|required_if:as,seller',
        ]);

        // Make conditional of as depending if as is member, seller or admin
        switch ($request->as) {
            case 'member':
                // Get chatrooms from user
                $chatrooms = request()->user()->chatrooms->unique('id');
                // Filter out chatrooms where user is not current_participant that is member
                $chatrooms = $chatrooms->filter(function ($chatroom) {
                    return $chatroom->participants
                        ->where('user_id', request()->user()->id)
                        ->where('role_flag', Role::MEMBER)
                        ->count() > 0;
                });
                // Return as json
                return response()->json($chatrooms->map(function ($chatroom) {
                    return $chatroom->getDetailsAsMember();
                }), 200);
                break;
            case 'seller':

                // If user doesn't manages the store, return error
                if (!$user->managesStore($request->store_id)) {
                    return response()->json(['message' => 'You do not manage this store'], 403);
                }
                // Get all chatrooms with store_id from request and user is a seller of
                $chatrooms = $user->stores()->find($request->store_id)->chatrooms()->get();
                return response()->json($chatrooms->map(function ($chatroom) {
                    return $chatroom->getDetailsAsStore();
                }), 200);
                break;
            case 'admin':
                // If user is not admin return error
                if (!$user->hasRole(Role::ADMINISTRATOR)) {
                    return response()->json(['message' => 'You are not an admin'], 403);
                }
                return response()->json(Chatroom::where('is_admin', true)->get()->map(function ($chatroom) {
                    return $chatroom->getDetailsAsAdmin();
                }), 200);
            default:
                return response(['message' => 'Invalid as'], 400);
        }
    }


    /**
     * Get or create chatroom with participant ID if it's not there
     *
     * Used to create chatroom, usually when a user is chatting/making an offer to a seller or admin
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validate fields from request where sending_as is required and within the string "member", "seller" or "admin"
        // and store_id exists in store table
        // and user_id exists in user table
        $this->validate($request, [
            'as' => 'string|required|in:member,seller,admin',
            'store_id' => 'exists:stores,id|required_if:as,seller',
            'user_id' => 'exists:users,id',
            'message' => 'string',
        ]);
        $user = request()->user();
        $message = $request->message;
        $chatroom = null;
        $participant = null;

        if($user->role == Role::RESTRICTED){
            // Return you are restricted
            return response(['message' => 'You are restricted'], 403);
        }

        // Make conditional of as depending if as is member, seller or admin
        switch ($request->as) {
            case 'member':
                if (!$request->store_id) {
                    $chatroom = Chatroom::findOrCreateMemberAdmin($user->id);
                } else {
                    $chatroom = Chatroom::findOrCreateMemberStore($user->id, $request->store_id);
                }
                $participant = $chatroom->findOrCreateMemberParticipant($user->id);
                if ($message) {
                    $message = $participant->messages()->create([
                        'content' => $message,
                    ]);
                }
                // Return as json
                return response()->json([
                    'chatroom' => $chatroom->getDetailsAsMember(),
                    'content' => $message,
                    'me' => $participant
                ], 201);
            case 'seller':
                // Check if user is a seller of store_id
                if (!$user->managesStore($request->store_id)) {
                    return response(['message' => 'User is not a seller of store'], 400);
                }
                if (!$request->user_id) {
                    $chatroom = Chatroom::findOrCreateStoreAdmin($request->store_id);
                } else {
                    $chatroom = Chatroom::findOrCreateMemberStore($request->user_id, $request->store_id);
                }
                $participant = $chatroom->findOrCreateSellerParticipant($user->id, $request->store_id);
                if ($message) {
                    $message = $participant->messages()->create([
                        'content' => $message,
                    ]);
                }
                // Return as json
                return response()->json([
                    'chatroom' => $chatroom->getDetailsAsStore(),
                    'content' => $message,
                    'me' => $participant
                ], 201);
            case 'admin':
                // Check if user is admin
                if (($user->role & Role::ADMINISTRATOR) != Role::ADMINISTRATOR) {
                    return response(['message' => 'User is not admin'], 400);
                }
                if ($request->user_id) {
                    $chatroom = Chatroom::findOrCreateMemberAdmin($request->user_id);
                } else if ($request->store_id) {
                    $chatroom = Chatroom::findOrCreateStoreAdmin($request->store_id);
                } else {
                    return response()->json(['message' => 'user_id or store_id is not declared!'], 400);
                }
                $participant = $chatroom->findOrCreateAdminParticipant($user->id);
                if ($message) {
                    $message = $participant->messages()->create([
                        'content' => $message,
                    ]);
                }
                // Return as json
                return response()->json([
                    'chatroom' => $chatroom->getDetailsAsAdmin(),
                    'content' => $message,
                    'me' => $participant
                ], 201);
        }
        return response(['message' => 'Invalid as'], 400);
    }

    /**
     * Display the specified chatroom all messages. Becomes a participant of chatroom.
     *
     * Used when a user clicked chat recipient
     *
     * @param  Request  $request
     * @return \Illuminate\Http\Response
     */
    public function initial_messages(Request $request)
    {
        $user = request()->user();

        // If user role is restricted return restricted
        if($user->role == Role::RESTRICTED){
            return response(['message' => 'You are restricted'], 403);
        }
        // Validate chatroom_id as chatroom and as as string
        // Both request are required
        $this->validate($request, [
            'chatroom_id' => 'required|exists:chatrooms,id',
            'as' => 'string|required|in:member,seller,admin',
        ]);
        $chatroom = Chatroom::cast(Chatroom::find($request->chatroom_id));

        $participant = null;
        switch ($request->as) {
            case 'member':
                // If chatroom is not a member chatroom (by not having user_id) return error
                if (!$chatroom->user_id) {
                    return response(['message' => 'Chatroom is not a member chatroom'], 400);
                }
                // Check if user is same as chatroom user_id
                if ($user->id != $chatroom->user_id) {
                    return response(['message' => 'You are not part of this chatroom'], 400);
                }
                $participant = $chatroom->findOrCreateMemberParticipant($user->id);
                break;
            case 'seller':
                // If chatroom is not a store (by not having store_id) return error
                if (!$chatroom->store_id) {
                    return response(['message' => 'This chatroom is not a store'], 400);
                }
                // If user does not handle chatroom store_id return you don't have access to this store
                if (!$user->managesStore($chatroom->store_id)) {
                    return response(['message' => 'You don\'t have access to this store'], 400);
                }
                $participant = $chatroom->findOrCreateSellerParticipant($user->id, $chatroom->store_id);
                break;
            case 'admin':
                // If chatroom have both user_id and store_id return error
                if ($chatroom->user_id && $chatroom->store_id) {
                    return response(['message' => 'This chatroom is not an admin chatroom'], 400);
                }
                // If user is not administrator return you don't have the privilege
                if (($user->role & Role::ADMINISTRATOR) != Role::ADMINISTRATOR) {
                    return response(['message' => 'You don\'t have the privilege'], 400);
                }
                $participant = $chatroom->findOrCreateAdminParticipant($user->id);
                break;
        }
        // Load participant user
        $participant->load('user');
        // Return as json
        return response()->json([
            'chatroom' => $chatroom,
            'messages' => $chatroom->messages,
            'me' => $participant,

        ], 200);

    }

    /**
     * Display the specified chatroom all new messages.
     *
     * Used to update chatroom when user is active
     *
     * @param  Request  $request
     * @return \Illuminate\Http\Response
     */
    public function messages(Request $request)
    {
        // Validate request chatroom_id, created_at and as as string
        // Both request are required
        $this->validate($request, [
            'chatroom_id' => 'required|exists:chatrooms,id',
            'created_at' => 'date',
            'as' => 'string|required|in:member,seller,admin',
        ]);
        // Get chatroom
        $chatroom = Chatroom::cast(Chatroom::find($request->chatroom_id));

        // Check if user partake in this chatroom and if user is admin
        $participant = null;

        switch ($request->as) {
            case 'member':
                $participant = $chatroom->participants()->where('user_id', request()->user()->id)->where('role_flag', Role::MEMBER)->first();
                break;
            case 'seller':
                // If user does not handle chatroom store_id return you don't have access to this store
                if (!request()->user()->stores()->find($chatroom->store_id)) {
                    return response(['message' => 'You don\'t have access to this store'], 400);
                }
                $participant = $chatroom->participants()->where('user_id', request()->user()->id)->where('role_flag', Role::SELLER)->first();
                break;
            case 'admin':
                // If user is not administrator return you don't have the privilege
                if ((request()->user()->role & Role::ADMINISTRATOR) != Role::ADMINISTRATOR) {
                    return response(['message' => 'You don\'t have the privilege'], 400);
                }
                $participant = $chatroom->participants()->where('user_id', request()->user()->id)->where('role_flag', Role::ADMINISTRATOR)->first();
                break;
        }

        if($participant == null){
            return response(['message' => 'You do not partake in this chatroom'], 400);
        }


        // Get created_at
        $created_at = $request->created_at;

        // Check if created_at is null, if null set it as Chatroom's creation date
        if(!$created_at){
            $created_at = $chatroom->created_at;
        }

        // Get messages after created_at
        $messages = $chatroom->messages()->where('chat_messages.created_at', '>', $created_at)->get();

        return $messages;
    }

    /**
     * Send message
     *
     * Used to send message to chatroom
     *
     * @param  Request  $request
     * return \Illuminate\Http\Response
     */
    public function send_message(Request $request)
    {
        // Validate request chat_participant_id as participant and content as string
        $this->validate($request, [
            'chat_participant_id' => 'required|exists:chat_participants,id',
            'message' => 'required|string',
        ]);

        $user = request()->user();
        // Get participant
        $participant = ChatParticipant::find($request->chat_participant_id);

        // Check user role
        if($user->role == Role::RESTRICTED){
            return response(['message' => 'You are restricted'], 403);
        }
        else if ($participant->role_flag & $user->role == 0){
            // Return you do not have the roles for this action
            return response(['message' => 'You do not have the roles for this action'], 403);
        }

        $message = $participant->messages()->create([
            'content' => $request->message,
        ]);

        // Check if message is created, if yes, return creation success
        if($message){
            return response()->json([
                'message' => 'Message created successfully',
                'content' => $message,
            ], 201);
        }
        // If not return creation failed
        return response()->json([
            'message' => 'Message creation failed'
        ], 400);
    }
}
