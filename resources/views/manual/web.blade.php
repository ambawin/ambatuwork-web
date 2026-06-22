<x-app-layout>
    <x-slot name="title">AmbatuWork | Web Version Manual</x-slot>

    <!-- Custom CSS for Smooth Scrolling and Active Nav states -->
    <style>
        html {
            scroll-behavior: smooth;
            scroll-padding-top: 6rem; /* Ensures sticky nav doesn't cover headings on anchor click */
        }
    </style>

    <div class="max-w-4xl mx-auto px-6 py-12 md:py-20">
        <!-- Breadcrumbs / Back button -->
        <div class="mb-8">
            <a href="{{ route('manual.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-[#977926] hover:text-[#604B10] transition">
                <x-heroicon-s-arrow-left class="w-4 h-4"/>
                <span>Back to manual selection</span>
            </a>
        </div>

        <!-- Documentation Content in a Clean One-Column Article -->
        <article class="w-full bg-white/70 p-8 sm:p-12 rounded-3xl border border-white/50 flex flex-col gap-12 text-[#6E5003]">
            
            <!-- Introduction -->
            <section id="introduction" class="flex flex-col gap-4">
                <h1 class="text-3xl sm:text-4xl font-black text-[#604B10] tracking-tight border-b border-[#604B10]/15 pb-4">
                    Web Version Manual
                </h1>
                <p class="text-base sm:text-lg leading-relaxed font-medium">
                    Welcome to <strong class="font-bold text-[#604B10]">AmbatuWork</strong>, a collaboration platform designed specifically to enforce accountability and promote equal contribution in student group projects.
                </p>
                <p class="text-sm sm:text-base leading-relaxed font-medium">
                    Group projects are notorious for unequal distribution of labor, often leaving one or two members to carry the entire workload. AmbatuWork solves this by implementing the agile <strong class="font-bold text-[#604B10]">SCRUM framework</strong> to break down project goals into structured, time-bounded phases (sprints) while tracking individual contributions transparently. Follow this comprehensive guide to configure, run, and close your project workspaces.
                </p>
            </section>

            <!-- Inline Table of Contents / Quick Navigation -->
            <section class="bg-white/40 p-6 rounded-2xl border border-white/20">
                <h3 class="text-xs font-black uppercase tracking-wider text-[#604B10] mb-4">
                    Quick Navigation
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <a href="#project-setup" class="group flex items-center gap-3 p-3 rounded-xl bg-white/40 hover:bg-white/90 border border-white/10 hover:border-[#604B10]/20 transition-all duration-300">
                        <div class="w-8 h-8 rounded-lg bg-[#604B10]/10 group-hover:bg-[#604B10]/20 text-[#604B10] flex items-center justify-center font-bold text-sm">1</div>
                        <span class="text-sm font-bold text-[#977926] group-hover:text-[#604B10] transition-colors">Workspace & Project Setup</span>
                    </a>
                    <a href="#backlog-management" class="group flex items-center gap-3 p-3 rounded-xl bg-white/40 hover:bg-white/90 border border-white/10 hover:border-[#604B10]/20 transition-all duration-300">
                        <div class="w-8 h-8 rounded-lg bg-[#604B10]/10 group-hover:bg-[#604B10]/20 text-[#604B10] flex items-center justify-center font-bold text-sm">2</div>
                        <span class="text-sm font-bold text-[#977926] group-hover:text-[#604B10] transition-colors">Backlog & DoD</span>
                    </a>
                    <a href="#sprint-board" class="group flex items-center gap-3 p-3 rounded-xl bg-white/40 hover:bg-white/90 border border-white/10 hover:border-[#604B10]/20 transition-all duration-300">
                        <div class="w-8 h-8 rounded-lg bg-[#604B10]/10 group-hover:bg-[#604B10]/20 text-[#604B10] flex items-center justify-center font-bold text-sm">3</div>
                        <span class="text-sm font-bold text-[#977926] group-hover:text-[#604B10] transition-colors">Sprint Board & Standups</span>
                    </a>
                    <a href="#sprint-review-retro" class="group flex items-center gap-3 p-3 rounded-xl bg-white/40 hover:bg-white/90 border border-white/10 hover:border-[#604B10]/20 transition-all duration-300">
                        <div class="w-8 h-8 rounded-lg bg-[#604B10]/10 group-hover:bg-[#604B10]/20 text-[#604B10] flex items-center justify-center font-bold text-sm">4</div>
                        <span class="text-sm font-bold text-[#977926] group-hover:text-[#604B10] transition-colors">Closure & Peer Reviews</span>
                    </a>
                </div>
            </section>

            <!-- Section 1: Workspace & Project Setup -->
            <section id="project-setup" class="flex flex-col gap-4 scroll-mt-24">
                <h2 class="text-2xl font-black text-[#604B10] tracking-tight border-b border-[#604B10]/10 pb-2">
                    1. Workspace & Project Setup
                </h2>
                <p class="text-sm sm:text-base leading-relaxed font-medium">
                    To start collaborating, your team must set up their accounts, create a shared workspace, and connect team members.
                </p>
                
                <h3 class="text-lg font-bold text-[#604B10] mt-2">Authentication & Session Management</h3>
                <p class="text-sm sm:text-base leading-relaxed font-medium">
                    AmbatuWork features secure integration via Google OAuth. To ensure seamless login:
                </p>
                <ul class="list-disc pl-6 flex flex-col gap-2 text-sm sm:text-base font-medium">
                    <li>Always log in using your university or official student email to match invitation records.</li>
                    <li>For security, user sessions are protected using secure cookies with HttpOnly and SameSite flags, and will automatically expire after periods of inactivity.</li>
                </ul>

                <h3 class="text-lg font-bold text-[#604B10] mt-2">Creating a Project Workspace</h3>
                <p class="text-sm sm:text-base leading-relaxed font-medium">
                    Only one team member needs to initialize the project:
                </p>
                <ul class="list-disc pl-6 flex flex-col gap-2 text-sm sm:text-base font-medium">
                    <li>Navigate to the <span class="bg-white/60 px-2 py-0.5 rounded text-[#604B10] font-bold">Dashboard</span> and click on <strong class="font-bold text-[#604B10]">Create Project</strong>.</li>
                    <li>Provide a clear <strong class="font-bold text-[#604B10]">Project Title</strong> (e.g., "Web App Dev Group 4"), a descriptive <strong class="font-bold text-[#604B10]">Description</strong> outlining high-level goals, and choose the overall project timeline.</li>
                    <li>The creator is automatically designated as the <strong class="font-bold text-[#604B10]">Project Owner</strong>. The owner holds permissions to configure sprints, edit project metadata, and manage team composition.</li>
                </ul>

                <h3 class="text-lg font-bold text-[#604B10] mt-2">Inviting Teammates (Collaborators)</h3>
                <p class="text-sm sm:text-base leading-relaxed font-medium">
                    Once the workspace is ready, invite your team:
                </p>
                <ul class="list-disc pl-6 flex flex-col gap-2 text-sm sm:text-base font-medium">
                    <li>Go to the <span class="bg-white/60 px-2 py-0.5 rounded text-[#604B10] font-bold">Settings</span> page of your active project.</li>
                    <li>Enter the Google email addresses of your team members. The email entered must exactly match the email they use to log into AmbatuWork.</li>
                    <li>Teammates will receive a notification and see a pending invite banner on their dashboards. They can join the project workspace with a single click.</li>
                </ul>
            </section>

            <!-- Section 2: Backlog & DoD -->
            <section id="backlog-management" class="flex flex-col gap-4 scroll-mt-24">
                <h2 class="text-2xl font-black text-[#604B10] tracking-tight border-b border-[#604B10]/10 pb-2">
                    2. Product Backlog & Definition of Done (DoD)
                </h2>
                <p class="text-sm sm:text-base leading-relaxed font-medium">
                    The Product Backlog acts as a centralized repository for all features, task cards, bug reports, and research assignments. Properly defining these items avoids ambiguity down the line.
                </p>

                <h3 class="text-lg font-bold text-[#604B10] mt-2">Creating Backlog Items</h3>
                <p class="text-sm sm:text-base leading-relaxed font-medium">
                    When creating tasks, ensure you capture the following parameters:
                </p>
                <ul class="list-disc pl-6 flex flex-col gap-2 text-sm sm:text-base font-medium">
                    <li><strong class="font-bold text-[#604B10]">Title & User Story format:</strong> Express requirements from the user's perspective (e.g. <i>"As a user, I want to filter search results by date so I can find recent posts quickly"</i>).</li>
                    <li><strong class="font-bold text-[#604B10]">Priority Level:</strong> Categorize as Low, Medium, or High. This guides the team when selecting items during sprint planning.</li>
                    <li><strong class="font-bold text-[#604B10]">Story Points (Relative Estimation):</strong> Estimate effort using the standard Fibonacci sequence:
                        <ul class="list-circle pl-6 mt-1 flex flex-col gap-1 text-xs sm:text-sm">
                            <li><strong class="text-[#604B10]">1 - 2 Points:</strong> Minor adjustments (e.g., changing colors, fixing copy typos, updating small text).</li>
                            <li><strong class="text-[#604B10]">3 - 5 Points:</strong> Standard features (e.g., designing an input form, writing a database query, styling a dashboard widget).</li>
                            <li><strong class="text-[#604B10]">8+ Points:</strong> Complex tasks (e.g., implementing OAuth integrations, complex multi-table migrations, writing end-to-end tests). <i>Recommendation: Break 8+ point tasks down into multiple smaller cards.</i></li>
                        </ul>
                    </li>
                </ul>

                <h3 class="text-lg font-bold text-[#604B10] mt-2">Definition of Done (DoD)</h3>
                <p class="text-sm sm:text-base leading-relaxed font-medium">
                    Before tasks can be completed, the team must align on the Definition of Done. The DoD acts as a quality gate that prevents members from claiming code or tasks are "finished" prematurely when they are half-baked.
                </p>
                <div class="bg-[#FDCB40]/10 border-l-4 border-[#604B10] p-5 rounded-r-2xl my-2 text-sm sm:text-base font-medium flex flex-col gap-3">
                    <p><strong class="font-bold text-[#604B10]">Configuring the DoD checklist:</strong></p>
                    <p class="text-xs sm:text-sm leading-relaxed">
                        The Project Owner configurations define the standard checklist items required. Typical criteria include:
                    </p>
                    <ul class="list-disc pl-6 text-xs sm:text-sm flex flex-col gap-1.5">
                        <li>Code passes linting checks and has no debug statements or console logs.</li>
                        <li>Unit tests are written and run successfully (100% pass rate).</li>
                        <li>Feature has been manually validated on mobile, tablet, and desktop viewports.</li>
                        <li>Code has been merged into the development branch and peer-reviewed by at least one other developer.</li>
                        <li>User-facing documentation or API references have been updated.</li>
                    </ul>
                </div>
            </section>

            <!-- Section 3: Sprints, Board & Daily Standups -->
            <section id="sprint-board" class="flex flex-col gap-4 scroll-mt-24">
                <h2 class="text-2xl font-black text-[#604B10] tracking-tight border-b border-[#604B10]/10 pb-2">
                    3. Sprint Planning, Board & Daily Standups
                </h2>
                <p class="text-sm sm:text-base leading-relaxed font-medium">
                    Sprints break down your project's roadmap into fixed, manageable cycles (typically 1 to 2 weeks) to maintain momentum.
                </p>

                <h3 class="text-lg font-bold text-[#604B10] mt-2">Planning and Launching a Sprint</h3>
                <p class="text-sm sm:text-base leading-relaxed font-medium">
                    To start a new iteration, navigate to the <span class="bg-white/60 px-2 py-0.5 rounded text-[#604B10] font-bold">Sprint Panel</span>:
                </p>
                <ul class="list-disc pl-6 flex flex-col gap-2 text-sm sm:text-base font-medium">
                    <li>Define a clear <strong class="font-bold text-[#604B10]">Sprint Goal</strong> (e.g. <i>"Deploy authentication infrastructure and database schema"</i>) so the team stays focused.</li>
                    <li>Set the duration (e.g., 7 or 14 days). Once the sprint begins, the duration cannot be changed.</li>
                    <li>Drag backlog cards into the Sprint backlog, assign tasks directly to teammates, and click <strong class="font-bold text-[#604B10]">Start Sprint</strong>.</li>
                </ul>

                <h3 class="text-lg font-bold text-[#604B10] mt-2">The Interactive Sprint Board</h3>
                <p class="text-sm sm:text-base leading-relaxed font-medium">
                    As work progresses, team members drag and drop cards across columns to visualize status transitions:
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 my-4 text-center text-xs sm:text-sm font-bold">
                    <div class="bg-white/50 p-4 rounded-xl border border-white/30 text-[#6E5003]">
                        <p class="text-xs uppercase text-[#977926]">1. Planned</p>
                        <p class="mt-1 font-black">To Do</p>
                        <p class="text-[10px] font-medium text-[#977926] mt-2">Tasks scheduled for development</p>
                    </div>
                    <div class="bg-white/50 p-4 rounded-xl border border-white/30 text-amber-800">
                        <p class="text-xs uppercase text-amber-600/70">2. Active</p>
                        <p class="mt-1 font-black">In Progress</p>
                        <p class="text-[10px] font-medium text-amber-600 mt-2">Assignee is currently working on it</p>
                    </div>
                    <div class="bg-white/50 p-4 rounded-xl border border-white/30 text-blue-800">
                        <p class="text-xs uppercase text-blue-600/70">3. Awaiting QA</p>
                        <p class="mt-1 font-black">In Review</p>
                        <p class="text-[10px] font-medium text-blue-600 mt-2">Pending peer review against the DoD</p>
                    </div>
                    <div class="bg-white/80 p-4 rounded-xl border border-white/60 text-[#604B10]">
                        <p class="text-xs uppercase text-[#977926]">4. Validated</p>
                        <p class="mt-1 font-black">Done</p>
                        <p class="text-[10px] font-medium text-[#977926] mt-2">All DoD checklist items fully verified</p>
                    </div>
                </div>

                <h3 class="text-lg font-bold text-[#604B10] mt-2">Daily Check-ins (Standups)</h3>
                <p class="text-sm sm:text-base leading-relaxed font-medium">
                    To enforce accountability and prevent team silos, every member must submit a Daily Check-in form for each day of an active sprint:
                </p>
                <ul class="list-decimal pl-6 flex flex-col gap-2 text-sm sm:text-base font-medium">
                    <li><strong class="text-[#604B10]">What did I accomplish yesterday?</strong> Describe specific tasks or bug fixes completed (avoid vague replies like "wrote code").</li>
                    <li><strong class="text-[#604B10]">What will I focus on today?</strong> Outline the precise items from your sprint board allocations you plan to tackle.</li>
                    <li><strong class="text-[#604B10]">What impediments/blockers are in my way?</strong> Identify technical blockers, missing access keys, or dependencies on others.</li>
                </ul>

                <h3 class="text-lg font-bold text-[#604B10] mt-2">Impediments and Blocker Alerts</h3>
                <p class="text-sm sm:text-base leading-relaxed font-medium">
                    If you encounter a blocker, checking the "Blocked" box creates an active impediment on your task card. This flags the project overview with a prominent notification banner, alerting the Scrum Master and team to jump in and assist immediately.
                </p>
            </section>

            <!-- Section 4: Closure & Peer Reviews -->
            <section id="sprint-review-retro" class="flex flex-col gap-4 scroll-mt-24">
                <h2 class="text-2xl font-black text-[#604B10] tracking-tight border-b border-[#604B10]/10 pb-2">
                    4. Sprint Closure, Retrospectives & Peer Reviews
                </h2>
                <p class="text-sm sm:text-base leading-relaxed font-medium">
                    When the sprint timeline runs out, the Project Owner initiates the closure workflow. This is where individual contributions are verified and peer feedback is generated.
                </p>

                <h3 class="text-lg font-bold text-[#604B10] mt-2">Closing a Sprint</h3>
                <p class="text-sm sm:text-base leading-relaxed font-medium">
                    During sprint closure:
                </p>
                <ul class="list-disc pl-6 flex flex-col gap-2 text-sm sm:text-base font-medium">
                    <li>Tasks in the **Done** column are finalized and archived in the sprint history.</li>
                    <li>Any incomplete tasks in *To Do*, *In Progress*, or *In Review* are returned to the Product Backlog, ensuring they are reassessed and re-estimated for future sprints.</li>
                </ul>

                <h3 class="text-lg font-bold text-[#604B10] mt-2">Continuous Improvement (Retrospective)</h3>
                <p class="text-sm sm:text-base leading-relaxed font-medium">
                    The retrospective provides a platform to address workflow issues and optimize team dynamics using three categories:
                </p>
                <ul class="list-disc pl-6 flex flex-col gap-2 text-sm sm:text-base font-medium">
                    <li><strong class="font-bold text-[#604B10]">Keep:</strong> Positive processes to maintain (e.g., <i>"Clear documentation on Git branching model helped avoid merge conflicts."</i>).</li>
                    <li><strong class="font-bold text-[#604B10]">Start:</strong> Adjustments or tools to adopt (e.g., <i>"Run database migrations locally before pushing."</i>).</li>
                    <li><strong class="font-bold text-[#604B10]">Stop:</strong> Inefficiencies to eliminate (e.g., <i>"Waiting until the last two days of the sprint to review peer pull requests."</i>).</li>
                </ul>

                <h3 class="text-lg font-bold text-[#604B10] mt-2">Peer Reviews & The Accountability Engine</h3>
                <p class="text-sm sm:text-base leading-relaxed font-medium">
                    The core mechanism of accountability in AmbatuWork is the anonymous Peer Review process. At the end of every sprint, teammates evaluate each other across 4 criteria:
                </p>
                <div class="bg-white/60 p-6 rounded-2xl border border-white/40 text-sm sm:text-base font-medium flex flex-col gap-4">
                    <p class="font-bold text-[#604B10] border-b border-[#604B10]/10 pb-2">The Evaluation Criteria:</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs sm:text-sm">
                        <div class="flex flex-col gap-1">
                            <span class="font-bold text-[#604B10]">1. Contribution</span>
                            <span class="text-[#6E5003]/80">Volume of task cards completed, complexity of work handled, and effort put into project deliverables.</span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="font-bold text-[#604B10]">2. Quality</span>
                            <span class="text-[#6E5003]/80">Adherence to the Definition of Done (DoD), writing robust code, and minimizing bug introductions.</span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="font-bold text-[#604B10]">3. Cooperation</span>
                            <span class="text-[#6E5003]/80">Responsiveness in communication channels, active participation in discussions, and supportiveness.</span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="font-bold text-[#604B10]">4. Reliability</span>
                            <span class="text-[#6E5003]/80">Fulfilling commitments, submitting daily check-ins consistently, and meeting agreed deadlines.</span>
                        </div>
                    </div>
                    
                    <p class="text-xs sm:text-sm mt-2 border-t border-[#604B10]/10 pt-4 leading-relaxed">
                        <strong class="font-bold text-[#604B10]">Accountability Index:</strong>
                        These anonymous reviews are aggregated to calculate a contribution index displayed on the dashboard. This index highlights each individual's relative effort, making slacking visible and giving instructors/Scrum Masters a data-driven overview of team contribution distribution.
                    </p>
                </div>
            </section>

        </article>
    </div>
</x-app-layout>
