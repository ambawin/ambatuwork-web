@php
$plans = [
    [
        'name' => 'Free',
        'description' => 'For individuals and small group projects.',
        'prices' => [
            'monthly' => 'Rp 0',
            'yearly' => 'Rp 0',
        ],
        'subtext' => 'Free forever, no credit card required.',
        'yearly_subtext' => null,
        'features' => [
            ['text' => 'Up to 2 active projects', 'included' => true],
            ['text' => 'Up to 3 team members', 'included' => true],
            ['text' => 'Android App', 'included' => true],
            ['text' => 'Peer reviews', 'included' => false],
            ['text' => 'Exportable PDF / spreadsheets reports', 'included' => false],
        ],
        'button' => [
            'text' => 'Login for free',
            'link' => route('login'),
            'primary' => false,
        ],
        'popular' => false,
    ],
    [
        'name' => 'Student',
        'description' => 'For active students.',
        'prices' => [
            'monthly' => 'Rp 32.000',
            'yearly' => 'Rp 25.000',
        ],
        'subtext' => 'For a price of a coffee',
        'yearly_subtext' => null,
        'features' => [
            ['text' => 'Everything in free, plus', 'included' => true],
            ['text' => 'Up to 5 active projects', 'included' => true],
            ['text' => 'Up to 10 members / projects', 'included' => true],
            ['text' => 'Peer reviews', 'included' => true],
            ['text' => 'Exportable PDF / spreadsheets reports', 'included' => true],
        ],
        'button' => [
            'text' => 'I want this',
            'link' => route('login'),
            'primary' => true,
        ],
        'popular' => true,
    ],
    [
        'name' => 'Professional',
        'description' => 'For organizations and professionals.',
        'prices' => [
            'monthly' => 'Rp 107.000',
            'yearly' => 'Rp 99.000',
        ],
        'subtext' => 'Greatest investment you\'ll ever need',
        'yearly_subtext' => null,
        'features' => [
            ['text' => 'Everything in student, plus', 'included' => true],
            ['text' => 'Unlimited projects', 'included' => true],
            ['text' => 'Unlimited members / projects', 'included' => true],
        ],
        'button' => [
            'text' => 'I want this instead',
            'link' => route('login'),
            'primary' => false,
        ],
        'popular' => false,
    ],
];
@endphp

<div x-data="{ billingPeriod: 'monthly' }">
    <!-- Interactive Billing Switcher -->
    <div class="text-center max-w-3xl mx-auto mb-16">
        <div class="mt-10 inline-flex items-center justify-center bg-white/40 backdrop-blur-md p-1.5 rounded-full border border-[#6E5003]/10">
            <button 
                @click="billingPeriod = 'monthly'"
                :class="billingPeriod === 'monthly' ? 'bg-[#604B10] text-white' : 'text-[#604B10] hover:text-[#977926]'"
                class="px-6 py-2.5 rounded-full text-sm font-bold tracking-wide transition duration-300 ease-in-out cursor-pointer"
            >
                Monthly Billing
            </button>
            <button 
                @click="billingPeriod = 'yearly'"
                :class="billingPeriod === 'yearly' ? 'bg-[#604B10] text-white' : 'text-[#604B10] hover:text-[#977926]'"
                class="px-6 py-2.5 rounded-full text-sm font-bold tracking-wide transition duration-300 ease-in-out flex items-center gap-2 cursor-pointer"
            >
                Yearly Billing
                <span class="bg-[#FDCB40] text-[#604B10] text-[10px] font-black px-2 py-0.5 rounded-full uppercase tracking-wider">
                    Save ~20%
                </span>
            </button>
        </div>
    </div>

    <!-- Pricing Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-stretch mb-24">
        @foreach ($plans as $plan)
            <!-- PLAN CARD: {{ $plan['name'] }} -->
            <div class="{{ $plan['popular'] 
                ? 'bg-white rounded-3xl p-8 flex flex-col border-2 border-[#604B10]/40 relative transition duration-300' 
                : 'bg-white/50 backdrop-blur-md border border-white/40 rounded-3xl p-8 flex flex-col transition duration-300' }}">
                
                @if ($plan['popular'])
                    <div class="absolute top-0 right-1/2 translate-x-1/2 translate-y-[-50%] bg-[#604B10] text-[#FDCB40] text-[11px] font-black tracking-widest px-4 py-1.5 rounded-full uppercase shadow">
                        Most Popular
                    </div>
                @endif

                <div class="mb-6{{ $plan['popular'] ? ' mt-2' : '' }}">
                    <h3 class="text-xl font-black text-[#604B10] mb-2 uppercase tracking-wider">{{ $plan['name'] }}</h3>
                    <p class="text-sm font-medium text-[#977926] min-h-[40px]">{{ $plan['description'] }}</p>
                </div>

                <!-- Price Block -->
                <div class="mb-8">
                    <div class="flex items-baseline">
                        <span class="text-4xl font-black text-[#604B10] tracking-tight" 
                              x-text="billingPeriod === 'monthly' ? '{{ $plan['prices']['monthly'] }}' : '{{ $plan['prices']['yearly'] }}'">
                            {{ $plan['prices']['monthly'] }}
                        </span>
                        <span class="text-[#977926] font-semibold text-sm ml-2">/ month</span>
                    </div>
                    @if ($plan['yearly_subtext'])
                        <p class="text-xs text-[#977926] mt-1 font-semibold min-h-[16px]">
                            <span x-show="billingPeriod === 'yearly'" style="display: none;">{{ $plan['yearly_subtext'] }}</span>
                            <span x-show="billingPeriod === 'monthly'">&nbsp;</span>
                        </p>
                    @elseif ($plan['subtext'])
                        <p class="text-xs text-[#977926]/70 mt-1 font-medium">{{ $plan['subtext'] }}</p>
                    @else
                        <p class="text-xs text-[#977926] mt-1 font-semibold min-h-[16px]">&nbsp;</p>
                    @endif
                </div>

                <!-- Feature List -->
                <ul class="space-y-4 mb-8 flex-grow">
                    @foreach ($plan['features'] as $feature)
                        @if ($feature['included'])
                            <li class="flex items-start gap-3">
                                <x-heroicon-s-check-badge class="w-6 h-6" />
                                <span class="text-sm font-semibold text-[#6E5003]/90">{{ $feature['text'] }}</span>
                            </li>
                        @else
                            <li class="flex items-start gap-3 text-[#977926]/60">
                                <x-heroicon-s-x-circle class="w-6 h-6" />
                                <span class="text-sm font-medium">{{ $feature['text'] }}</span>
                            </li>
                        @endif
                    @endforeach
                </ul>

                <!-- Button -->
                <a href="{{ $plan['button']['link'] }}" 
                   class="block w-full text-center font-bold py-3.5 px-6 rounded-full transition {{ $plan['button']['primary'] 
                       ? 'bg-[#604B10] hover:bg-[#977926] text-white' 
                       : 'bg-white hover:bg-white/70 text-[#604B10] border border-[#6E5003]/10' }}">
                    {{ $plan['button']['text'] }}
                </a>
            </div>
        @endforeach
    </div>
</div>