<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Quizzes — EduAI</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #0f0f1a; color: #e2e8f0; min-height: 100vh; }
        .navbar { background: rgba(255,255,255,0.05); backdrop-filter: blur(20px); border-bottom: 1px solid rgba(255,255,255,0.1); padding: 1rem 2rem; display: flex; align-items: center; justify-content: space-between; }
        .logo { font-size: 1.4rem; font-weight: 700; background: linear-gradient(135deg, #7c3aed, #06b6d4); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .nav-links a { color: #94a3b8; text-decoration: none; margin-left: 1.5rem; font-size: 0.9rem; transition: color .2s; }
        .nav-links a:hover { color: #fff; }
        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; }
        .page-title { font-size: 1.8rem; font-weight: 700; }
        .btn-primary { background: linear-gradient(135deg, #7c3aed, #06b6d4); color: white; border: none; padding: .75rem 1.5rem; border-radius: 12px; font-weight: 600; cursor: pointer; text-decoration: none; transition: opacity .2s, transform .2s; display: inline-flex; align-items: center; gap: .5rem; }
        .btn-primary:hover { opacity: .85; transform: translateY(-1px); }
        .alert { padding: 1rem 1.5rem; border-radius: 12px; margin-bottom: 1.5rem; }
        .alert-success { background: rgba(16,185,129,.15); border: 1px solid rgba(16,185,129,.3); color: #34d399; }
        .quiz-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem; }
        .quiz-card { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 1.5rem; transition: transform .2s, border-color .2s; }
        .quiz-card:hover { transform: translateY(-4px); border-color: #7c3aed; }
        .quiz-card-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1rem; }
        .quiz-title { font-size: 1.05rem; font-weight: 600; color: #f1f5f9; line-height: 1.4; }
        .badge { padding: .25rem .75rem; border-radius: 999px; font-size: .75rem; font-weight: 600; }
        .badge-pending { background: rgba(234,179,8,.15); color: #fbbf24; }
        .badge-processing { background: rgba(59,130,246,.15); color: #60a5fa; }
        .badge-done { background: rgba(16,185,129,.15); color: #34d399; }
        .badge-failed { background: rgba(239,68,68,.15); color: #f87171; }
        .quiz-meta { display: flex; gap: 1rem; margin: .75rem 0; flex-wrap: wrap; }
        .meta-item { display: flex; align-items: center; gap: .4rem; color: #94a3b8; font-size: .8rem; }
        .quiz-card-actions { display: flex; gap: .75rem; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.08); }
        .btn-sm { padding: .45rem 1rem; border-radius: 8px; font-size: .82rem; font-weight: 500; text-decoration: none; border: none; cursor: pointer; transition: all .2s; }
        .btn-view { background: rgba(124,58,237,.2); color: #a78bfa; border: 1px solid rgba(124,58,237,.3); }
        .btn-view:hover { background: rgba(124,58,237,.4); }
        .btn-danger { background: rgba(239,68,68,.15); color: #f87171; border: 1px solid rgba(239,68,68,.2); }
        .btn-danger:hover { background: rgba(239,68,68,.3); }
        .empty-state { text-align: center; padding: 4rem 2rem; }
        .empty-icon { font-size: 4rem; margin-bottom: 1rem; }
        .empty-state h3 { font-size: 1.4rem; margin-bottom: .5rem; }
        .empty-state p { color: #64748b; margin-bottom: 1.5rem; }
        .pagination { display: flex; justify-content: center; margin-top: 2rem; gap: .5rem; }
        .pagination a, .pagination span { padding: .5rem 1rem; border-radius: 8px; background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.1); color: #94a3b8; text-decoration: none; font-size: .85rem; }
        .pagination .active { background: rgba(124,58,237,.3); border-color: #7c3aed; color: #a78bfa; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">EduAI</div>
        <div class="nav-links">
            <a href="{{ route('dashboard') }}">Dashboard</a>
            <a href="{{ route('quiz.index') }}" style="color:#a78bfa">My Quizzes</a>
            <a href="{{ route('quiz.create') }}">+ New Quiz</a>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title">🧠 My Quizzes</h1>
            <a href="{{ route('quiz.create') }}" class="btn-primary">
                <span>+</span> Create Quiz
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if($quizzes->isEmpty())
            <div class="empty-state">
                <div class="empty-icon">📝</div>
                <h3>No quizzes yet</h3>
                <p>Create your first AI-powered quiz from any text, PDF, or document.</p>
                <a href="{{ route('quiz.create') }}" class="btn-primary">Create Your First Quiz</a>
            </div>
        @else
            <div class="quiz-grid">
                @foreach($quizzes as $quiz)
                    <div class="quiz-card">
                        <div class="quiz-card-header">
                            <div class="quiz-title">{{ $quiz->title }}</div>
                            <span class="badge badge-{{ $quiz->status }}">
                                @if($quiz->status === 'processing') ⏳ @elseif($quiz->status === 'done') ✅ @elseif($quiz->status === 'failed') ❌ @else 🕐 @endif
                                {{ ucfirst($quiz->status) }}
                            </span>
                        </div>

                        <div class="quiz-meta">
                            <span class="meta-item">📄 {{ ucfirst($quiz->source_type) }}</span>
                            <span class="meta-item">❓ {{ $quiz->questions_count }} Questions</span>
                            <span class="meta-item">👥 {{ $quiz->attempts_count }} Attempts</span>
                            @if($quiz->is_public)
                                <span class="meta-item">🔗 Public</span>
                            @endif
                        </div>

                        <div class="quiz-card-actions">
                            <a href="{{ route('quiz.show', $quiz) }}" class="btn-sm btn-view">View</a>
                            <form method="POST" action="{{ route('quiz.destroy', $quiz) }}" style="margin-left:auto"
                                onsubmit="return confirm('Delete this quiz?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-sm btn-danger">Delete</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="pagination">
                {{ $quizzes->links() }}
            </div>
        @endif
    </div>
</body>
</html>
