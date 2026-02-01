<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Template;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log; // লগিং অ্যাড করা হয়েছে

class AdminTemplateController extends Controller
{
    public function index()
    {
        $templates = Template::latest()->get();
        return view('admin.templates.index', compact('templates'));
    }

    public function builder()
    {
        return view('admin.templates.builder');
    }

    public function store(Request $request)
    {
        try {
            // ১. ভ্যালিডেশন
            $request->validate([
                'name' => 'required|string|max:255',
                'frame_image' => 'required|image|max:5120', // ৫ মেগাবাইট পর্যন্ত এলাউড
                'layout_data' => 'required', // JSON চেক শিথিল করা হলো
            ]);

            // ২. ফ্রেম আপলোড
            if ($request->hasFile('frame_image')) {
                $file = $request->file('frame_image');
                $filename = 'frame_' . time() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('templates/frames', $filename, 'public');
                $frameUrl = asset('storage/' . $path);
            } else {
                return response()->json(['success' => false, 'message' => 'Frame Image not found!'], 422);
            }

            // ৩. থাম্বনেইল আপলোড (যদি থাকে)
            $thumbUrl = null;
            if ($request->filled('thumbnail_base64')) {
                $image = $request->input('thumbnail_base64');
                // বেস৬৪ স্ট্রিং ক্লিন করা
                if (preg_match('/^data:image\/(\w+);base64,/', $image, $type)) {
                    $image = substr($image, strpos($image, ',') + 1);
                    $type = strtolower($type[1]); // jpg, png, gif
                    $image = base64_decode($image);
                    if ($image !== false) {
                        $thumbName = 'thumb_' . time() . '.' . $type;
                        Storage::disk('public')->put('templates/thumbs/' . $thumbName, $image);
                        $thumbUrl = asset('storage/templates/thumbs/' . $thumbName);
                    }
                }
            }

            // ৪. ডাটাবেসে সেভ
            Template::create([
                'name' => $request->name,
                'frame_url' => $frameUrl,
                'thumbnail_url' => $thumbUrl ?? $frameUrl,
                'layout_data' => json_decode($request->layout_data, true), // অ্যারে হিসেবে সেভ হবে
                'is_active' => true
            ]);

            return response()->json(['success' => true, 'redirect' => route('admin.templates.index')]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation Error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error("Template Save Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $template = Template::findOrFail($id);
        // ফাইল ডিলিট করার লজিক (অপশনাল)
        // Storage::disk('public')->delete(str_replace(asset('storage/'), '', $template->frame_url));
        $template->delete();
        return back()->with('success', 'টেমপ্লেট ডিলিট করা হয়েছে।');
    }
}