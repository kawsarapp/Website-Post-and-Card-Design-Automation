@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 text-gray-800 fw-bold">🎨 Template Gallery</h4>
            <p class="text-muted small mb-0">Manage your news card designs.</p>
        </div>
        <a href="{{ route('admin.templates.builder') }}" class="btn btn-primary shadow-sm">
            <i class="fas fa-plus-circle me-1"></i> Create New Template
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <strong>Success!</strong> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4">
        @forelse($templates as $template)
            <div class="col-md-6 col-lg-4 col-xl-3">
                <div class="card h-100 shadow-sm border-0 hover-shadow transition-all">
                    <div class="position-relative">
                        {{-- থাম্বনেইল ইমেজ --}}
                        <img src="{{ $template->thumbnail_url }}" class="card-img-top p-2 bg-light" alt="{{ $template->name }}" style="height: 200px; object-fit: contain;">
                        
                        <div class="position-absolute top-0 end-0 p-2">
                            <span class="badge bg-success shadow-sm">Active</span>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <h6 class="card-title fw-bold text-dark mb-1">{{ $template->name }}</h6>
                        <small class="text-muted d-block mb-3">Created: {{ $template->created_at->format('d M, Y') }}</small>
                        
                        <div class="d-grid gap-2">
                            {{-- ডিলিট বাটন --}}
                            <form action="{{ route('admin.templates.destroy', $template->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this template?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                                    <i class="fas fa-trash-alt me-1"></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="text-center py-5 bg-white rounded shadow-sm">
                    <div class="mb-3 text-muted" style="font-size: 3rem;">📂</div>
                    <h5 class="fw-bold text-gray-600">No Templates Found</h5>
                    <p class="text-muted mb-4">Get started by creating your first news card design.</p>
                    <a href="{{ route('admin.templates.builder') }}" class="btn btn-primary px-4">
                        Create Template
                    </a>
                </div>
            </div>
        @endforelse
    </div>
</div>

<style>
    .hover-shadow:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
    }
    .transition-all {
        transition: all 0.3s ease;
    }
</style>
@endsection