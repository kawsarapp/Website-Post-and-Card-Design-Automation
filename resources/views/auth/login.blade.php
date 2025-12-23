<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Newsmange24</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        input::-ms-reveal, input::-ms-clear { display: none; }
        .face-container { transition: all 0.3s ease; }
        .hands-up #eye-l, .hands-up #eye-r { opacity: 0; }
        .hands-up #hand-l { transform: translateY(-15px) translateX(10px) rotate(15deg); }
        .hands-up #hand-r { transform: translateY(-15px) translateX(-10px) rotate(-15deg); }
        .is-smiling #mouth-normal { opacity: 0; }
        .is-smiling #mouth-smile { opacity: 1; }
        #hand-l, #hand-r, #mouth-normal, #mouth-smile { transition: all 0.3s ease; transform-origin: center; }
        body { min-height: 100vh; display: flex; flex-direction: column; }
    </style>
</head>
<body class="bg-gray-100 overflow-x-hidden">

    <header class="fixed top-0 w-full bg-white shadow-sm py-4 px-6 z-50 text-center">
        <h1 class="text-xl md:text-2xl font-bold text-indigo-600">Newsmange24</h1>
    </header>

    <main class="flex-grow flex items-center justify-center p-4 mt-20 md:mt-16">
        <div class="bg-white p-6 md:p-8 rounded-2xl shadow-xl w-full max-w-[400px] border border-gray-200">
            
            <div class="flex justify-center mb-6">
                <div id="avatar" class="face-container relative w-20 h-20 md:w-24 md:h-24 bg-indigo-50 rounded-full flex items-center justify-center border-4 border-indigo-100">
                    <svg viewBox="0 0 100 100" class="w-16 h-16 md:w-20 md:h-20">
                        <circle cx="50" cy="50" r="40" fill="#F9FAFB" stroke="#4F46E5" stroke-width="2"/>
                        <g id="eyes">
                            <circle id="eye-l" cx="35" cy="45" r="4" fill="#374151"/>
                            <circle id="eye-r" cx="65" cy="45" r="4" fill="#374151"/>
                        </g>
                        <path id="hand-l" d="M15,80 Q25,60 35,80" stroke="#4F46E5" stroke-width="8" fill="none" stroke-linecap="round"/>
                        <path id="hand-r" d="M85,80 Q75,60 65,80" stroke="#4F46E5" stroke-width="8" fill="none" stroke-linecap="round"/>
                        <path id="mouth-normal" d="M40,65 Q50,75 60,65" stroke="#4F46E5" stroke-width="2" fill="none"/>
                        <path id="mouth-smile" d="M35,65 Q50,85 65,65" stroke="#4F46E5" stroke-width="2" fill="none" class="opacity-0"/>
                    </svg>
                </div>
            </div>

            @if ($errors->any())
                <div class="bg-red-100 text-red-700 p-3 rounded-lg mb-4 text-sm">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST" class="space-y-5">
                @csrf
                <div>
                    <label class="block text-sm font-semibold text-gray-600 ml-1">Email Address</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required 
                           placeholder="yourname@email.com"
                           class="w-full mt-1.5 p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-600 ml-1">Password</label>
                    <div class="relative mt-1.5">
                        <input type="password" id="password" name="password" required 
                               placeholder="••••••••"
                               class="w-full p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none pr-12 transition-all">
                        
                        <button type="button" id="toggleBtn" class="absolute inset-y-0 right-0 px-4 flex items-center text-gray-400">
                            <svg id="eyeSvg" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="w-full bg-indigo-600 text-white py-3.5 rounded-xl font-bold text-lg hover:bg-indigo-700 transition-all shadow-lg">
                    Log In
                </button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-sm text-gray-500">Don't have an account? <a href="https://wa.me/8801771545972" target="_blank" class="text-indigo-600 font-bold hover:underline">Contact</a></p>
            </div>
        </div>
    </main>

    <script>
        // ... (আগের স্ক্রিপ্টটি এখানে থাকবে)
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const avatar = document.getElementById('avatar');
        const toggleBtn = document.getElementById('toggleBtn');

        emailInput.addEventListener('focus', () => {
            avatar.classList.remove('hands-up');
            avatar.classList.add('is-smiling');
        });

        emailInput.addEventListener('blur', () => {
            avatar.classList.remove('is-smiling');
        });

        passwordInput.addEventListener('focus', () => {
            avatar.classList.remove('is-smiling');
            avatar.classList.add('hands-up');
        });

        toggleBtn.addEventListener('click', () => {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            if(isPassword) {
                avatar.classList.remove('hands-up');
                avatar.classList.add('is-smiling'); 
            } else {
                avatar.classList.add('hands-up');
                avatar.classList.remove('is-smiling');
            }
        });

        document.addEventListener('click', (e) => {
            if (!emailInput.contains(e.target) && !passwordInput.contains(e.target) && !toggleBtn.contains(e.target)) {
                avatar.classList.remove('hands-up');
                avatar.classList.remove('is-smiling');
            }
        });
    </script>
</body>
</html>