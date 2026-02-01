<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>নিউজ প্রিভিউ - সাব-এডিটর বিডি</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
	
	<style>
    /* SolaimanLipi Font Import */
    @import url('https://fonts.maateen.me/solaiman-lipi/font.css');

    /* Font Family Update */
    .font-bangla { 
        font-family: 'SolaimanLipi', Arial, sans-serif; 
    }

    /* অন্যান্য স্টাইল অপরিবর্তিত রাখা হয়েছে */
    @keyframes shimmer { 
        0% { background-position: -200% 0; } 
        100% { background-position: 200% 0; } 
    }
    
    .skeleton { 
        background: linear-gradient(90deg, #f3f4f6 25%, #e5e7eb 50%, #f3f4f6 75%); 
        background-size: 200% 100%; 
        animation: shimmer 1.5s infinite; 
    }
    
    .tox-tinymce-aux { 
        z-index: 99999 !important; 
    }
	.preview-container { max-width: 800px; background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }

</style>


</head>
<body>
    <div class="container preview-container">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="text-center mb-4">
            <span class="badge bg-secondary">নিউজ ড্রাফট প্রিভিউ (অপ্রকাশিত)</span>
        </div>

        <h1 class="fw-bold mb-4">{{ $news->ai_title ?? $news->title }}</h1>
        <img src="{{ $news->thumbnail_url }}" class="img-fluid rounded-4 mb-4 shadow-sm">
        
        <div class="news-content mb-5" style="font-size: 19px; line-height: 1.8; color: #333;">
            {!! $news->ai_content ?? $news->content !!}
        </div>

        <hr>
        <div class="feedback-section py-4 text-center">
            <h5 class="mb-3 fw-bold">এই নিউজটি কি পাবলিশ করা যাবে?</h5>
            <div class="d-flex justify-content-center gap-3">
                <form action="{{ route('news.preview-feedback', $news->id) }}" method="POST">
                    @csrf
                    <input type="hidden" name="status" value="approved">
                    <button type="submit" class="btn btn-success btn-lg px-5 shadow">✅ হ্যাঁ, ঠিক আছে</button>
                </form>

                <button class="btn btn-danger btn-lg px-5 shadow" data-bs-toggle="collapse" data-bs-target="#rejectNote">❌ না, পরিবর্তন লাগবে</button>
            </div>

            <div class="collapse mt-4" id="rejectNote">
                <form action="{{ route('news.preview-feedback', $news->id) }}" method="POST">
                    @csrf
                    <input type="hidden" name="status" value="rejected">
                    <textarea name="note" class="form-control mb-2" placeholder="কী পরিবর্তন করতে হবে এখানে লিখুন..." required></textarea>
                    <button type="submit" class="btn btn-dark w-100">ফিডব্যাক পাঠান</button>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>