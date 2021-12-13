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
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
    }

    /**
     * Create chatroom by member to seller
     */
    public function chat_member_to_seller(Request $request)
    {
        // Get store_id from route
        $store_id = $request->route('store_id');

        // Find store
        $store = Store::find($store_id);

        // If store is not found
        if (!$store) {
            return response()->json([
                'message' => 'Store not found',
            ], 404);
        }

        // Find chatroom with store_id and user_id
        $chatroom = Chatroom::where('store_id', $store_id)
            ->where('user_id', $request->user()->id)
            ->first();

        if($chatroom) {
            // Return chatroom with participants and details
            return response()->json([
                'chatroom' => $chatroom,
                'details' => $chatroom->details,
            ], 200);
        }


        $chatroom = new Chatroom();
        $chatroom->store_id = $store_id;
        $chatroom->user_id = $request->user()->id;
        $chatroom->save();
        if(!$chatroom->save()){
            return response()->json([
                'message' => 'Chatroom not created',
            ], 404);
        }
        $chatroom->addMemberParticipant($request->user());
        $chatroom->addSellerParticipants($store);

        // Return chatroom with participants and messages
        return response()->json([
            'chatroom' => $chatroom,
            'details' => $chatroom->getDetailsAsMember(),
        ], 200);
    }


    /**
     * Create chatroom by admin to member
     */
    public function chat_admin_to_member(Request $request)
    {
        // Get member_id from route
        $member_id = $request->route('member_id');

        // Find member
        $member = User::find($member_id);

        // If store is not found
        if (!$member) {
            return response()->json([
                'message' => 'Member not found',
            ], 404);
        }

        // Find a chatroom containing member and chatroom is_admin
        $chatroom = Chatroom::where('user_id', $member_id)
            ->where('is_admin', true)
            ->first();

        if($chatroom){
            return response()->json([
                'chatroom' => $chatroom,
                'details' => $chatroom->getDetailsAsAdmin(),
            ], 200);
        }


        $chatroom = new Chatroom();
        $chatroom->user_id = $member->id;
        $chatroom->is_admin = true;
        $chatroom->save();
        if(!$chatroom->save()){
            return response()->json([
                'message' => 'Chatroom not created',
            ], 404);
        }
        $chatroom->addAdminParticipants();
        $chatroom->addMemberParticipant($member);

        return response()->json([
            'chatroom' => $chatroom,
            'details' => $chatroom->getDetailsAsAdmin(),
        ], 200);
    }


    /**
     * Store message as user
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store_member(Request $request)
    {
        $this->validate($request, [
            'content' => 'required|string',
        ]);

        // Get chatroom_id from route
        $chatroom_id = $request->route('chatroom_id');

        // Check if chatroom exists
        $chatroom = request()->user()->chatrooms()->find($chatroom_id);
        if (!$chatroom) {
            return response()->json(['message' => 'Chatroom not found'], 404);
        }

        // Get current_participant from chatroom with user and role is member
        $current_participant = ChatParticipant::where('chatroom_id', $chatroom_id)
            ->where('user_id', $request->user()->id)
            ->where('role_flag', Role::MEMBER)
            ->first();

        // Check if current_participant exists
        if (!$current_participant) {
            return response()->json(['message' => 'You are not part of this chat'], 404);
        }

        // Create message
        $result = $chatroom->messages()->create([
            'chat_participant_id' => $current_participant->id,
            'content' => $request->content,
        ]);

        if($result) {
            return response()->json(['message' => 'Message created'], 200);
        } else {
            return response()->json(['message' => 'Message not created'], 404);
        }
    }

    /**
     * Store message as seller
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store_seller(Request $request)
    {
        $this->validate($request, [
            'content' => 'required|string',
        ]);

        // Get chatroom_id from route
        $chatroom_id = $request->route('chatroom_id');

        // Check if chatroom exists
        $chatroom = Chatroom::find($chatroom_id);
        if (!$chatroom) {
            return response()->json(['message' => 'Chatroom not found'], 404);
        }

        // Get current_participant from chatroom with user and role is seller
        $current_participant = $chatroom->participants()->where('user_id', $request->user()->id)
                                                ->where('role_flag', Role::SELLER)->first();

        // Check if current_participant exists
        if (!$current_participant) {
            return response()->json(['message' => 'Participant not found'], 404);
        }

        // Check if current_participant no longer handler of store
        if (!$current_participant->store->handlers->contains($request->user())) {
            return response()->json(['message' => 'User is not handler of store'], 404);
        }

        // Create message
        $result = $chatroom->messages()->create([
            'chat_participant_id' => $current_participant->id,
            'content' => $request->content,
        ]);

        if($result) {
            return response()->json(['message' => 'Message created', 'data'=> $request], 200);
        } else {
            return response()->json(['message' => 'Message not created'], 404);
        }
    }

    /**
     * Store message as admin
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store_admin(Request $request)
    {
        $this->validate($request, [
            'content' => 'required|string',
        ]);

        // Get chatroom_id from route
        $chatroom_id = $request->route('chatroom_id');

        // Check if chatroom exists
        $chatroom = Chatroom::find($chatroom_id);
        if (!$chatroom) {
            return response()->json(['message' => 'Chatroom not found'], 404);
        }

        // Get current_participant from chatroom with user and role is admin
        $current_participant = $chatroom->participants()->where('user_id', $request->user()->id)
                                                ->where('role_flag', Role::ADMINISTRATOR)->first();

        // Check if current_participant exists
        if (!$current_participant) {
            return response()->json(['message' => 'Participant not found'], 404);
        }

        // Create message
        $result = $chatroom->messages()->create([
            'chat_participant_id' => $current_participant->id,
            'content' => $request->content,
        ]);

        if($result) {
            return response()->json(['message' => 'Message created', 'data'=> $request], 200);
        } else {
            return response()->json(['message' => 'Message not created'], 404);
        }
    }

    /**
     * Get messages of chatroom as member
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function messages_member(Request $request)
    {
        // Get chatroom_id from route
        $chatroom_id = $request->route('chatroom_id');

        // Check if chatroom exists
        $chatroom = request()->user()->chatrooms()->find($chatroom_id);
        if (!$chatroom) {
            return response()->json(['message' => 'Chatroom not found'], 404);
        }

        // Check if member is current_participant of chatroom and role is member
        $current_participant = $chatroom->participants()->where('user_id', $request->user()->id)
                                                ->where('role_flag', Role::MEMBER)->first();
        if (!$current_participant) {
            return response()->json(['message' => 'Participant not found'], 404);
        }

        $chatroom->load('messages');
        $chatroom->participants->load('user');
        $my_id = $current_participant->id;
        // Create a variable of other participants than current_participant
        $other_participants = $chatroom->participants->filter(function ($current_participant) use ($my_id) {
            return $current_participant->id != $my_id;
        });

        // // Check if there is one admin within other participants
        // $is_admin = $other_participants->filter(function ($current_participant) {
        //     return $current_participant->role_flag == Role::ADMINISTRATOR;
        // });

        // // Check store id within other participants if it exists
        // $store = $other_participants->filter(function ($current_participant) {
        //     return $current_participant->store_id != null;
        // })->first()->store;

        return response()->json([
            'chatroom' => $chatroom,
            'me' => $current_participant,
            'details' => $chatroom->getDetailsAsMember(),
        ], 200);
    }

    /**
     * Get messages of chatroom as seller
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function messages_seller(Request $request)
    {
        // Get chatroom_id from route
        $chatroom_id = $request->route('chatroom_id');

        // Check if chatroom exists
        $chatroom = request()->user()->chatrooms()->find($chatroom_id);
        if (!$chatroom) {
            return response()->json(['message' => 'Chatroom not found'], 404);
        }

        // Check if user is current_participant of chatroom and role is seller
        $current_participant = $chatroom->participants()->where('user_id', $request->user()->id)
                                                ->where('role_flag', Role::SELLER)->first();
        if (!$current_participant) {
            return response()->json(['message' => 'Participant not found'], 404);
        }

        // Check if user is handler of store
        if (!$current_participant->store->handlers->contains($request->user())) {
            return response()->json(['message' => 'User is not handler of store'], 404);
        }

        $chatroom->load('messages');
        $chatroom->participants->load('user');

        // Create a variable of other participants than current_participant
        $other_participants = $chatroom->participants->filter(function ($current_participant) {
            return $current_participant->store_id == null;
        });

        // Check if there is one admin within other participants
        $is_admin = $other_participants->filter(function ($current_participant) {
            return $current_participant->role_flag == Role::ADMINISTRATOR;
        });

        // Get first member if it exists
        $member = $other_participants->filter(function ($current_participant) {
            return $current_participant->role_flag == Role::MEMBER;
        })->first();

        return response()->json([
            'chatroom' => $chatroom,
            'me' => $current_participant,
            'details' => $chatroom->getDetailsAsStore(),
        ], 200);
    }

    /**
     * Get messages of chatroom as admin
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function messages_admin(Request $request)
    {
        // Get chatroom_id from route
        $chatroom_id = $request->route('chatroom_id');


        // Check if chatroom exists
        $chatroom = Chatroom::find($chatroom_id);
        if (!$chatroom) {
            return response()->json(['message' => 'Chatroom not found'], 404);
        }

        // Check if user is current_participant of chatroom and role is admin
        $current_participant = $chatroom->participants()->where('user_id', $request->user()->id)
                                                ->where('role_flag', Role::ADMINISTRATOR)->first();
        if (!$current_participant) {
            $chatroom->participants()->create([
                'user_id' => $request->user()->id,
                'role_flag' => Role::ADMINISTRATOR,
            ]);
        }

        $chatroom->load('messages');
        $chatroom->participants->load('user');

        return response()->json([
            'chatroom' => $chatroom,
            'me' => $current_participant,
            'details' => $chatroom->getDetailsAsAdmin(),
        ], 200);
    }

    /**
     * Get List of chatrooms as member
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function chatrooms_member(Request $request)
    {
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


    }

    /**
     * Get List of chatrooms as member
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function chatrooms_seller(Request $request)
    {
        // Get chatrooms from user
        $chatrooms = request()->user()->chatrooms->unique('id');
        // Filter out chatrooms where user is not current_participant that is seller
        $chatrooms = $chatrooms->filter(function ($chatroom) {
            return $chatroom->participants
                            ->where('user_id', request()->user()->id)
                            ->where('role_flag', Role::SELLER)
                            ->count() > 0;
        });
        // Return as json
        return response()->json($chatrooms->map(function ($chatroom) {
            return $chatroom->getDetailsAsStore();
        }), 200);


    }

    /**
     * Get List of chatrooms as admin
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function chatrooms_admin(Request $request)
    {
        // Get all chatrooms where is_admin is true
        $chatrooms = Chatroom::where('is_admin', true)->get();

        // Return as json
        return response()->json($chatrooms->map(function ($chatroom) {
            return $chatroom->getDetailsAsAdmin();
        }), 200);


    }




}
