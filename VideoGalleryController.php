<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\API\Role;
use App\Models\VideoGallery;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use DB;

class VideoGalleryController extends Controller
{
    public function index()
    {
        $data = VideoGallery::all();
        return view('video_gallery.index', compact('data'));
    }

    public function create()
    {
        return view('video_gallery.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'type'  => 'required|string|max:255',
            'description'  => 'nullable|string',
            'file'  => 'nullable|mimes:mp4,mov,avi,wmv,jpg,jpeg,png,gif,pdf|max:204800', 
        ]);

        $video = new VideoGallery();
        $video->title = $request->title;
        $video->type  = $request->type;
        $video->description  = $request->description;

       if ($request->hasFile('file')) {
            $file     = $request->file('file');
            $filename = time() . '_' . $file->getClientOriginalName();

            $file->move(public_path('video_gallery'), $filename);
            $video->url = 'video_gallery/' . $filename;
        }

        $video->save();

        return redirect()->route('video-gallery')->with('success', 'File saved successfully!');
    }

    public function edit($id)
    {
        $video = VideoGallery::find($id);
        return view('video_gallery.edit', compact('video'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'type'  => 'required|string|max:100',
            'description' => 'nullable|string',
            'file'  => 'nullable|mimes:mp4,mov,avi,wmv,jpg,jpeg,png,gif,pdf|max:204800', 
        ]);

        $video = VideoGallery::findOrFail($id);
        $video->title = $request->title;
        $video->type = $request->type;
        $video->description = $request->description;

        if ($request->hasFile('file')) {
            if ($video->file && Storage::exists('public/' . $video->file)) {
                Storage::delete('public/' . $video->file);
            }
            $filePath = $request->file('file')->store('video_gallery', 'public');
            $video->file = $filePath;
        }

        $video->save();

        return redirect()->route('video-gallery')->with('success', 'Video updated successfully!');
    }

    public function delete($id)
    {
        $video = VideoGallery::findOrFail($id);
        if($video) {
            $video->delete();
        }

        return redirect()->route('video-gallery')->with('success', 'Video Deleted successfully!');
    }


}
