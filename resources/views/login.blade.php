<x-app-layout>
    <x-slot name="title">Login | AmbatuWork</x-slot>

    <!-- Google Identity Services client script -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>

    <div class="w-full min-h-[calc(100vh-80px)] flex items-center justify-center p-6 text-[#6E5003] antialiased selection:bg-yellow-500/20">
        <div class="w-full max-w-[440px] flex flex-col items-center">
            <!-- Logo Header -->
            <a href="{{ route('landing') }}" class="flex items-center gap-2 mb-8 group transition-transform duration-300 hover:scale-105">
                <span class="text-3xl font-black tracking-tight text-[#604B10]">
                    AmbatuWork
                </span>
            </a>

            <!-- Login Card -->
            <div class="w-full bg-white rounded-3xl p-10 flex flex-col shadow-[0_10px_30px_rgba(0,0,0,0.06)] border border-[#FDCB40]/30">
                <div class="mb-8 text-center">
                    <h1 class="text-3xl font-black tracking-tight text-[#604B10] mb-3">Welcome back</h1>
                    <p class="text-sm text-[#977926] leading-relaxed max-w-[320px] mx-auto">
                        Sign in to manage your student group projects, track your sprints, and collaborate with your team.
                    </p>
                </div>

                <!-- Error Alerts Container -->
                <div id="error-container" class="hidden mb-6 p-4 rounded-xl border border-red-200 bg-red-50 text-red-700 text-sm flex items-start gap-3 transition-all duration-300">
                    <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <span id="error-message" class="font-semibold">Authentication failed. Please try again.</span>
                </div>

                <!-- Loading Spinner Container -->
                <div id="loading-container" class="hidden flex-col items-center justify-center py-6 mb-6">
                    <svg class="animate-spin h-8 w-8 text-[#FDCB40] mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="text-sm font-bold text-[#604B10]">Verifying with Google...</p>
                </div>

                <!-- Google Sign-In Buttons -->
                <div class="flex flex-col gap-4">
                    <!-- Mounted standard GIS Button -->
                    <div class="w-full flex justify-center py-2">
                        <div id="google-signin-btn" class="w-full flex justify-center"></div>
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
                    <div class="flex-grow border-t border-[#6E5003]/10"></div>
                    <span class="flex-shrink mx-4 text-xs font-bold uppercase tracking-wider text-[#977926]/60">Security</span>
                    <div class="flex-grow border-t border-[#6E5003]/10"></div>
                </div>

                <div class="text-xs text-center text-[#977926]/80 leading-relaxed font-medium">
                    Secured with Google Identity Services.
                </div>
            </div>

            <footer class="mt-12 text-center text-xs text-[#977926] font-semibold">
                &copy; 2026 AmbatuWork. All rights reserved.
            </footer>
        </div>
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
                document.getElementById('google-signin-btn').style.display = 'flex';
                
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
                    theme: "outline", // Clean, minimal theme for white background
                    size: "large",
                    text: "signin_with",
                    shape: "pill", // Circular, smooth modern shape
                    logo_alignment: "left",
                    width: 360 // Complete width alignment with standard card structure
                }
            );

            // Optional One Tap overlay trigger
            google.accounts.id.prompt();
        };
    </script>
</x-app-layout>
