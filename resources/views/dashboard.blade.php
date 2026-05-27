<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | AmbatuWork</title>
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Google Fonts: Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS v4 -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: radial-gradient(circle at top right, rgba(249, 115, 22, 0.04), transparent 500px),
                        radial-gradient(circle at bottom left, rgba(244, 63, 94, 0.04), transparent 500px),
                        #0B0B0C;
        }

        .glass-card {
            background: rgba(22, 22, 24, 0.6);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .glass-card-hover {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .glass-card-hover:hover {
            transform: translateY(-4px);
            background: rgba(26, 26, 28, 0.8);
            border-color: rgba(249, 115, 22, 0.2);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.4);
        }

        .avatar-ring {
            border: 2px solid #0B0B0C;
        }
    </style>
</head>
<body class="min-h-full flex flex-col text-gray-200 antialiased selection:bg-orange-500/20">

    <!-- Top Navigation Bar -->
    <nav class="glass-card border-b border-white/5 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-6 h-16 flex justify-between items-center">
            
            <!-- Logo Section -->
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2 group transition-transform duration-300">
                <span class="text-2xl font-bold tracking-tight bg-gradient-to-r from-orange-400 via-pink-500 to-rose-500 bg-clip-text text-transparent">
                    AmbatuWork
                </span>
            </a>

            <!-- Right Profile Section -->
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-3">
                    <!-- User Details -->
                    <div class="hidden sm:flex flex-col text-right">
                        <span class="text-sm font-semibold text-white leading-tight">{{ $user->name }}</span>
                        <span class="text-[11px] text-gray-500 font-medium tracking-wide">{{ $user->email }}</span>
                    </div>

                    <!-- User Avatar -->
                    <div class="relative group">
                        <img class="w-10 h-10 rounded-full bg-neutral-800 border border-white/10 shadow" 
                             src="{{ $user->avatar_url ?: 'https://www.gravatar.com/avatar/'.md5(strtolower(trim($user->email))).'?d=mp' }}" 
                             alt="{{ $user->name }}">
                        <span class="absolute bottom-0 right-0 w-3 h-3 bg-emerald-500 rounded-full border-2 border-[#0B0B0C] title='Active Session'"></span>
                    </div>
                </div>

                <!-- Vertical Divider -->
                <div class="h-6 w-px bg-white/10"></div>

                <!-- Secure Logout Trigger -->
                <a href="#" 
                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                   class="text-sm font-semibold text-gray-400 hover:text-white transition duration-200 flex items-center gap-1.5 py-2 px-3 rounded-lg hover:bg-white/5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                    <span class="hidden md:inline">Sign Out</span>
                </a>

                <!-- Hidden CSRF Logout Form (Prevents Logout CSRF exploits) -->
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                    @csrf
                </form>
            </div>
        </div>
    </nav>

    <!-- Main Workspace Container -->
    <main class="flex-grow max-w-7xl w-full mx-auto px-6 py-10">
        
        <!-- Welcome Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-10">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-white mb-2">Hello, {{ explode(' ', $user->name)[0] }}!</h1>
                <p class="text-sm text-gray-400">Manage your sprints, assign tasks, and monitor contribution scores.</p>
            </div>
            
            <div class="flex items-center gap-3">
                <!-- Action Buttons Placeholders -->
                <a href="#" class="bg-gradient-to-r from-orange-500 to-rose-500 text-white font-semibold text-sm py-2.5 px-4 rounded-xl shadow-lg shadow-orange-500/10 hover:shadow-orange-500/20 hover:scale-[1.02] transition duration-200">
                    + Create Project
                </a>
            </div>
        </div>

        <!-- Metrics Overview Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            
            <!-- Stat: Total Projects -->
            <div class="glass-card rounded-2xl p-6 flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 block mb-1">Total Projects</span>
                    <span class="text-3xl font-extrabold text-white">{{ $totalProjectsCount }}</span>
                </div>
                <div class="w-12 h-12 rounded-xl bg-orange-500/10 border border-orange-500/20 flex items-center justify-center text-orange-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                </div>
            </div>

            <!-- Stat: Active Sprints -->
            <div class="glass-card rounded-2xl p-6 flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 block mb-1">Active Sprints</span>
                    <span class="text-3xl font-extrabold text-white">{{ $activeSprintsCount }}</span>
                </div>
                <div class="w-12 h-12 rounded-xl bg-rose-500/10 border border-rose-500/20 flex items-center justify-center text-rose-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
            </div>

            <!-- Stat: Pending Invitations -->
            <div class="glass-card rounded-2xl p-6 flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 block mb-1">Pending Invitations</span>
                    <span class="text-3xl font-extrabold text-white">{{ $pendingInvitations->count() }}</span>
                </div>
                <div class="w-12 h-12 rounded-xl bg-pink-500/10 border border-pink-500/20 flex items-center justify-center text-pink-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Pending Invitations Actions Section (Shows conditionally) -->
        @if($pendingInvitations->count() > 0)
        <div class="mb-10">
            <h2 class="text-lg font-bold tracking-tight text-white mb-4 flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-pink-500 animate-pulse"></span>
                Review Pending Invitations
            </h2>
            <div class="grid grid-cols-1 gap-4">
                @foreach($pendingInvitations as $invite)
                <div class="glass-card rounded-2xl p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-l-4 border-l-pink-500">
                    <div>
                        <h3 class="font-bold text-white mb-1">Invitation to join &ldquo;{{ $invite->project->name }}&rdquo;</h3>
                        <p class="text-xs text-gray-400">
                            Invited by <span class="text-gray-300 font-medium">{{ $invite->invitedBy->name }}</span> ({{ $invite->invitedBy->email }}) &bull; Role: <span class="capitalize text-gray-300 font-semibold">{{ $invite->role }}</span>
                        </p>
                    </div>
                    <div class="flex items-center gap-3 shrink-0">
                        <button class="bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-xs py-2 px-4 rounded-xl transition duration-200">
                            Accept
                        </button>
                        <button class="bg-white/5 hover:bg-white/10 text-gray-400 hover:text-white font-semibold text-xs py-2 px-4 rounded-xl transition duration-200 border border-white/10">
                            Decline
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Split Grid: Owned Projects vs Collaborating Projects -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            
            <!-- Owned Projects Section -->
            <div>
                <h2 class="text-xl font-bold tracking-tight text-white mb-6 flex items-center gap-2">
                    <span class="w-1.5 h-4 bg-orange-500 rounded-full"></span>
                    My Projects
                    <span class="text-xs bg-orange-500/10 border border-orange-500/20 text-orange-400 px-2.5 py-0.5 rounded-full font-bold ml-1">
                        {{ $ownedProjects->count() }}
                    </span>
                </h2>
                
                @if($ownedProjects->isEmpty())
                <div class="glass-card rounded-2xl p-8 text-center text-gray-500 border border-dashed border-white/10">
                    <p class="text-sm font-medium mb-3">You haven't created any Scrum projects yet.</p>
                    <a href="#" class="inline-flex text-xs font-bold text-orange-400 hover:text-orange-300 hover:underline">Create your first project &rarr;</a>
                </div>
                @else
                <div class="flex flex-col gap-5">
                    @foreach($ownedProjects as $project)
                    <div class="glass-card glass-card-hover rounded-2xl p-6 flex flex-col justify-between h-full">
                        <div>
                            <div class="flex justify-between items-start gap-4 mb-3">
                                <h3 class="font-bold text-lg text-white hover:text-orange-400 transition leading-tight">
                                    {{ $project->name }}
                                </h3>
                                <span class="text-[10px] bg-orange-500/10 border border-orange-500/20 text-orange-400 font-extrabold uppercase tracking-widest px-2.5 py-0.5 rounded-full shrink-0">
                                    Owner
                                </span>
                            </div>
                            
                            <p class="text-xs text-gray-400 leading-relaxed mb-5 line-clamp-2">
                                {{ $project->description ?: 'No description provided.' }}
                            </p>

                            <!-- Active Sprint Badge / Goal -->
                            <div class="mb-5 p-3.5 rounded-xl bg-[#0F0F10] border border-white/5">
                                @if($project->activeSprint)
                                    <div class="flex items-center gap-2 mb-1.5">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                        <span class="text-xs font-bold text-white">{{ $project->activeSprint->name }}</span>
                                    </div>
                                    <p class="text-[11px] text-gray-500 line-clamp-1 italic mb-1">&ldquo;{{ $project->activeSprint->sprint_goal }}&rdquo;</p>
                                    <span class="text-[10px] text-gray-600 block">Ends {{ $project->activeSprint->end_date->format('M d, Y') }}</span>
                                @else
                                    <div class="flex items-center gap-2 text-gray-600">
                                        <span class="w-1.5 h-1.5 rounded-full bg-gray-700"></span>
                                        <span class="text-xs font-semibold">No active sprint</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Card Footer: Members Avatars and Actions -->
                        <div class="flex items-center justify-between border-t border-white/5 pt-4 mt-auto">
                            <!-- Members Stack -->
                            <div class="flex items-center">
                                @if($project->members->isEmpty())
                                    <span class="text-[10px] text-gray-600 font-medium">Just you</span>
                                @else
                                    <div class="flex -space-x-2 overflow-hidden">
                                        @foreach($project->members->take(4) as $member)
                                        <img class="inline-block h-6.5 w-6.5 rounded-full avatar-ring bg-neutral-800" 
                                             src="{{ $member->avatar_url ?: 'https://www.gravatar.com/avatar/'.md5(strtolower(trim($member->email))).'?d=mp' }}" 
                                             alt="{{ $member->name }}"
                                             title="{{ $member->name }}">
                                        @endforeach
                                        @if($project->members->count() > 4)
                                        <div class="flex items-center justify-center h-6.5 w-6.5 rounded-full bg-neutral-800 text-[9px] font-extrabold text-gray-400 avatar-ring">
                                            +{{ $project->members->count() - 4 }}
                                        </div>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            <!-- Action Shortcuts -->
                            <div class="flex items-center gap-2">
                                <a href="#" class="text-xs font-bold bg-white/5 hover:bg-white/10 text-gray-300 py-1.5 px-3 rounded-lg border border-white/5 transition">
                                    View Board
                                </a>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            <!-- Collaborating Projects Section -->
            <div>
                <h2 class="text-xl font-bold tracking-tight text-white mb-6 flex items-center gap-2">
                    <span class="w-1.5 h-4 bg-rose-500 rounded-full"></span>
                    Joined Projects
                    <span class="text-xs bg-rose-500/10 border border-rose-500/20 text-rose-400 px-2.5 py-0.5 rounded-full font-bold ml-1">
                        {{ $joinedProjects->count() }}
                    </span>
                </h2>
                
                @if($joinedProjects->isEmpty())
                <div class="glass-card rounded-2xl p-8 text-center text-gray-500 border border-dashed border-white/10">
                    <p class="text-sm font-medium mb-3">You aren't collaborating on any other projects.</p>
                    <span class="text-xs">Once you are invited by email, pending invitations will appear at the top.</span>
                </div>
                @else
                <div class="flex flex-col gap-5">
                    @foreach($joinedProjects as $project)
                    <div class="glass-card glass-card-hover rounded-2xl p-6 flex flex-col justify-between h-full">
                        <div>
                            <div class="flex justify-between items-start gap-4 mb-3">
                                <h3 class="font-bold text-lg text-white hover:text-rose-400 transition leading-tight">
                                    {{ $project->name }}
                                </h3>
                                <span class="text-[10px] bg-rose-500/10 border border-rose-500/20 text-rose-400 font-extrabold uppercase tracking-widest px-2.5 py-0.5 rounded-full shrink-0">
                                    {{ $project->pivot->role }}
                                </span>
                            </div>
                            
                            <p class="text-xs text-gray-400 leading-relaxed mb-5 line-clamp-2">
                                {{ $project->description ?: 'No description provided.' }}
                            </p>

                            <!-- Active Sprint Badge / Goal -->
                            <div class="mb-5 p-3.5 rounded-xl bg-[#0F0F10] border border-white/5">
                                @if($project->activeSprint)
                                    <div class="flex items-center gap-2 mb-1.5">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                        <span class="text-xs font-bold text-white">{{ $project->activeSprint->name }}</span>
                                    </div>
                                    <p class="text-[11px] text-gray-500 line-clamp-1 italic mb-1">&ldquo;{{ $project->activeSprint->sprint_goal }}&rdquo;</p>
                                    <span class="text-[10px] text-gray-600 block">Ends {{ $project->activeSprint->end_date->format('M d, Y') }}</span>
                                @else
                                    <div class="flex items-center gap-2 text-gray-600">
                                        <span class="w-1.5 h-1.5 rounded-full bg-gray-700"></span>
                                        <span class="text-xs font-semibold">No active sprint</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Card Footer: Owner and Actions -->
                        <div class="flex items-center justify-between border-t border-white/5 pt-4 mt-auto">
                            <!-- Owner Info -->
                            <div class="flex items-center gap-2">
                                <img class="h-6.5 w-6.5 rounded-full bg-neutral-800" 
                                     src="{{ $project->owner->avatar_url ?: 'https://www.gravatar.com/avatar/'.md5(strtolower(trim($project->owner->email))).'?d=mp' }}" 
                                     alt="{{ $project->owner->name }}"
                                     title="Owner: {{ $project->owner->name }}">
                                <span class="text-[11px] font-semibold text-gray-300 line-clamp-1 max-w-[120px]">{{ $project->owner->name }}</span>
                            </div>

                            <!-- Action Shortcuts -->
                            <div class="flex items-center gap-2">
                                <a href="#" class="text-xs font-bold bg-white/5 hover:bg-white/10 text-gray-300 py-1.5 px-3 rounded-lg border border-white/5 transition">
                                    View Board
                                </a>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="glass-card border-t border-white/5 py-6 mt-16 text-center text-xs text-gray-600">
        <div class="max-w-7xl mx-auto px-6 flex flex-col sm:flex-row justify-between items-center gap-2">
            <span>&copy; 2026 AmbatuWork. Engineered for transparency and student accountability.</span>
            <div class="flex gap-4">
                <a href="{{ route('landing') }}" class="hover:text-gray-400 transition">Landing Page</a>
            </div>
        </div>
    </footer>
</body>
</html>
