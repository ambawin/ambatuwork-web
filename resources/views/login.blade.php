<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | AmbatuWork</title>
    
    <!-- CSRF Token for Secure AJAX API Callback -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Google Fonts: Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS v4 -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Google Identity Services client script -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>

    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: radial-gradient(circle at top right, rgba(249, 115, 22, 0.08), transparent 400px),
                        radial-gradient(circle at bottom left, rgba(244, 63, 94, 0.08), transparent 400px),
                        #0B0B0C;
        }

        .glass-card {
            background: rgba(22, 22, 24, 0.6);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
        }

        .animate-gradient {
            background-size: 200% 200%;
            animation: gradient-shift 6s ease infinite;
        }

        @keyframes gradient-shift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
    </style>
</head>
<body class="h-full flex items-center justify-center p-6 text-gray-200 antialiased selection:bg-orange-500/20">

    <div class="w-full max-w-[440px] flex flex-col items-center">
        <!-- Logo Header -->
        <a href="{{ route('landing') }}" class="flex items-center gap-2 mb-8 group transition-transform duration-300 hover:scale-105">
            <span class="text-3xl font-extrabold tracking-tight bg-gradient-to-r from-orange-400 via-pink-500 to-rose-500 bg-clip-text text-transparent animate-gradient">
                AmbatuWork
            </span>
        </a>

        <!-- Login Card -->
        <div class="w-full glass-card rounded-2xl p-8 flex flex-col">
            <div class="mb-8">
                <h1 class="text-2xl font-semibold tracking-tight text-white mb-2">Welcome back</h1>
                <p class="text-sm text-gray-400 leading-relaxed">
                    Collaborate with your student group, track your sprint deliverables, and hold everyone accountable.
                </p>
            </div>

            <!-- Error Alerts Container -->
            <div id="error-container" class="hidden mb-6 p-4 rounded-xl border border-red-500/20 bg-red-500/10 text-red-400 text-sm flex items-start gap-3 transition-all duration-300">
                <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <span id="error-message">Authentication failed. Please try again.</span>
            </div>

            <!-- Loading Spinner Container -->
            <div id="loading-container" class="hidden flex-col items-center justify-center py-6 mb-6">
                <svg class="animate-spin h-8 w-8 text-orange-500 mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="text-sm font-medium text-gray-300">Verifying with Google...</p>
            </div>

            <!-- Google Sign-In Buttons -->
            <div class="flex flex-col gap-4">
                <!-- Mounted standard GIS Button -->
                <div class="w-full flex justify-center py-2">
                    <div id="google-signin-btn" class="w-full"></div>
                </div>

                <!-- Custom Decorative Google Button for Initial State -->
                <div id="custom-signin-placeholder" class="hidden w-full items-center justify-center gap-3 bg-white text-gray-900 font-semibold py-3 px-4 rounded-xl shadow transition duration-200 cursor-pointer">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                    </svg>
                    <span>Sign in with Google</span>
                </div>
            </div>

            <!-- Divider -->
            <div class="relative flex py-5 items-center">
                <div class="flex-grow border-t border-white/5"></div>
                <span class="flex-shrink mx-4 text-xs font-semibold uppercase tracking-wider text-gray-500">Security</span>
                <div class="flex-grow border-t border-white/5"></div>
            </div>

            <div class="text-xs text-center text-gray-500 leading-relaxed">
                Auth calls utilize cryptographically signed Google ID tokens over secure HTTPS connections.
            </div>
        </div>

        <footer class="mt-12 text-center text-xs text-gray-600">
            &copy; 2026 AmbatuWork. All rights reserved.
        </footer>
    </div>

    <!-- Google Identity Services Initialization script -->
    <script>
        // Callback handler invoked by Google Identity Services when user chooses an account
        function handleCredentialResponse(response) {
            // Hide standard sign-in interface and show the verified loader
            document.getElementById('google-signin-btn').style.display = 'none';
            document.getElementById('error-container').classList.add('hidden');
            document.getElementById('loading-container').classList.remove('hidden');

            const idToken = response.credential;
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            // Secure POST callback to login endpoint passing the ID Token with full CSRF protection
            fetch("{{ route('auth.google.callback', [], false) }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                    "Accept": "application/json"
                },
                body: JSON.stringify({
                    id_token: idToken
                })
            })
            .then(res => res.json().then(data => ({ status: res.status, body: data })))
            .then(result => {
                if (result.status === 200 && result.body.success) {
                    // Smooth visual transition to Dashboard
                    window.location.href = result.body.redirect_url;
                } else {
                    throw new Error(result.body.message || "Authentication failed on callback.");
                }
            })
            .catch(error => {
                console.error("Auth Callback Error:", error);
                
                // Show errors, hide loader, reset standard google login button view
                document.getElementById('loading-container').classList.add('hidden');
                document.getElementById('google-signin-btn').style.display = 'block';
                
                const errAlert = document.getElementById('error-container');
                const errMsg = document.getElementById('error-message');
                errMsg.textContent = error.message;
                errAlert.classList.remove('hidden');
            });
        }

        window.onload = function () {
            const googleWebClientId = "{{ config('services.google.web_client_id') }}";
            
            if (!googleWebClientId) {
                const errAlert = document.getElementById('error-container');
                const errMsg = document.getElementById('error-message');
                errMsg.textContent = "Google OAuth Client ID is missing. Please check your .env configuration.";
                errAlert.classList.remove('hidden');
                return;
            }

            // Initialize GIS
            google.accounts.id.initialize({
                client_id: googleWebClientId,
                callback: handleCredentialResponse,
                context: "signin",
                ux_mode: "popup", // Popup auth UX prevents tedious full page redirect loops
                auto_select: false
            });

            // Render Google Sign-in standard button inside DOM element
            google.accounts.id.renderButton(
                document.getElementById("google-signin-btn"),
                { 
                    type: "standard",
                    theme: "filled_blue", // Premium styling matching logo accents
                    size: "large",
                    text: "signin_with",
                    shape: "pill", // Circular, smooth modern shape
                    logo_alignment: "left",
                    width: 376 // Complete width alignment with standard card structure
                }
            );

            // Optional One Tap overlay trigger
            google.accounts.id.prompt();
        };
    </script>
</body>
</html>
