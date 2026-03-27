<?php

namespace App\Http\Controllers;

use App\Models\FacebookPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FacebookPageController extends Controller
{
    /**
     * নতুন Facebook Page Test করে Save করা
     */
    public function store(Request $request)
    {
        $request->validate([
            'page_id'      => 'required|string',
            'access_token' => 'required|string',
            'page_name'    => 'nullable|string|max:100',
            'comment_link' => 'nullable|boolean',
        ]);

        $pageId = trim($request->page_id);
        $token  = trim($request->access_token);

        try {
            $response = Http::timeout(10)->get("https://graph.facebook.com/v19.0/{$pageId}", [
                'fields'       => 'id,name',
                'access_token' => $token,
            ]);

            $data = $response->json();

            if (!$response->successful() || !isset($data['id'])) {
                $errorMsg = $data['error']['message'] ?? 'Unknown Facebook Error';
                return response()->json(['success' => false, 'message' => '❌ Test Failed: ' . $errorMsg]);
            }

            $fetchedName = $data['name'] ?? 'My Facebook Page';
            $finalName   = $request->filled('page_name') ? $request->page_name : $fetchedName;

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => '❌ Connection Error: ' . $e->getMessage()]);
        }

        $page = FacebookPage::create([
            'user_id'           => Auth::id(),
            'page_name'         => $finalName,
            'page_id'           => $pageId,
            'access_token'      => $token,
            'is_active'         => true,
            'is_studio_default' => true,
            'comment_link'      => $request->boolean('comment_link', false),
            'test_status'       => 'connected',
            'last_tested_at'    => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "✅ পেজ কানেক্টেড: {$finalName}",
            'page'    => [
                'id'                => $page->id,
                'page_name'         => $page->page_name,
                'page_id'           => $page->page_id,
                'is_active'         => $page->is_active,
                'is_studio_default' => $page->is_studio_default,
                'comment_link'      => $page->comment_link,
                'test_status'       => $page->test_status,
            ],
        ]);
    }

    /**
     * সেভ করা পেজ পুনরায় Test করা
     */
    public function test($id)
    {
        $page = FacebookPage::where('user_id', Auth::id())->findOrFail($id);

        try {
            $response = Http::timeout(10)->get("https://graph.facebook.com/v19.0/{$page->page_id}", [
                'fields'       => 'id,name',
                'access_token' => $page->access_token,
            ]);

            $data = $response->json();

            if ($response->successful() && isset($data['id'])) {
                $page->update(['test_status' => 'connected', 'last_tested_at' => now()]);
                return response()->json(['success' => true, 'message' => '✅ Connected: ' . ($data['name'] ?? $page->page_name)]);
            } else {
                $errorMsg = $data['error']['message'] ?? 'Unknown Error';
                $page->update(['test_status' => 'failed', 'last_tested_at' => now()]);
                return response()->json(['success' => false, 'message' => '❌ Failed: ' . $errorMsg]);
            }
        } catch (\Exception $e) {
            $page->update(['test_status' => 'failed', 'last_tested_at' => now()]);
            return response()->json(['success' => false, 'message' => '❌ Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Active / Inactive Toggle
     */
    public function toggle($id)
    {
        $page = FacebookPage::where('user_id', Auth::id())->findOrFail($id);
        $page->update(['is_active' => !$page->is_active]);

        return response()->json([
            'success'   => true,
            'is_active' => $page->is_active,
            'message'   => $page->is_active ? '✅ পেজ Active করা হয়েছে' : '⏸️ পেজ Inactive করা হয়েছে',
        ]);
    }

    /**
     * Studio Default Toggle — Studio modal এ auto-check হবে কিনা
     */
    public function setDefault($id)
    {
        $page = FacebookPage::where('user_id', Auth::id())->findOrFail($id);
        $page->update(['is_studio_default' => !$page->is_studio_default]);

        return response()->json([
            'success'           => true,
            'is_studio_default' => $page->is_studio_default,
            'message'           => $page->is_studio_default
                ? '✅ Studio তে default checked হবে'
                : '⬜ Studio তে default unchecked হবে',
        ]);
    }

    /**
     * পেজ Delete করা
     */
    public function destroy($id)
    {
        $page = FacebookPage::where('user_id', Auth::id())->findOrFail($id);
        $pageName = $page->page_name;
        $page->delete();

        return response()->json(['success' => true, 'message' => "🗑️ '{$pageName}' মুছে ফেলা হয়েছে।"]);
    }
}
