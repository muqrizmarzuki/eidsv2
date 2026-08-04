<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-IDS v2 — Electronic Inspection Defect System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-gray-50 font-sans text-gray-900 antialiased selection:bg-eids-accent selection:text-white flex flex-col">

    {{-- Top Navigation Bar --}}
    <header class="bg-eids-primary text-white border-b border-white/10 sticky top-0 z-30 shadow-md">
        <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-eids-accent rounded-xl flex items-center justify-center shrink-0 shadow-md ring-2 ring-white/10">
                    <span class="material-symbols-outlined filled text-white text-xl">domain</span>
                </div>
                <div>
                    <div class="text-white font-extrabold text-lg leading-none tracking-tight">E-IDS <span class="text-eids-light text-xs font-semibold">v2</span></div>
                    <div class="text-white/60 text-[10px] tracking-widest uppercase leading-tight mt-1 font-semibold">Electronic Inspection Defect System</div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                @auth
                    <a href="{{ url('/dashboard') }}"
                       class="min-h-[44px] px-5 py-2.5 bg-eids-accent text-white text-sm font-extrabold rounded-xl hover:bg-emerald-600 transition shadow-sm flex items-center gap-2">
                        <span class="material-symbols-outlined text-lg">grid_view</span>
                        Dashboard &rarr;
                    </a>
                @else
                    <a href="{{ route('login') }}"
                       class="min-h-[44px] px-6 py-2.5 bg-white text-eids-primary text-sm font-extrabold rounded-xl hover:bg-gray-100 transition shadow-sm flex items-center gap-2">
                        <span class="material-symbols-outlined text-lg">login</span>
                        Sign In
                    </a>
                @endauth
            </div>
        </div>
    </header>

    {{-- Hero Banner Section --}}
    <section class="bg-eids-primary text-white py-16 lg:py-24 relative overflow-hidden">
        <div class="absolute inset-0 opacity-10 pointer-events-none">
            <svg class="w-full h-full" viewBox="0 0 1000 600" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="500" cy="300" r="400" stroke="white" stroke-width="1.5"/>
                <circle cx="500" cy="300" r="250" stroke="white" stroke-width="1"/>
                <line x1="0" y1="300" x2="1000" y2="300" stroke="white" stroke-width="1"/>
            </svg>
        </div>

        <div class="max-w-7xl mx-auto px-6 relative z-10 text-center lg:text-left grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
            <div class="lg:col-span-7 space-y-6">
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/10 border border-white/15 text-eids-light text-xs font-extrabold uppercase tracking-widest">
                    <span class="w-2 h-2 rounded-full bg-eids-light animate-pulse"></span>
                    Malaysian Construction Sector E-IDS Standard
                </div>
                <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight leading-tight text-white">
                    Digitising Residential Building Defect Inspections
                </h1>
                <p class="text-white/80 text-base sm:text-lg leading-relaxed max-w-2xl font-medium">
                    The precision inspector's ledger. Streamline site defect recording, automated sample size calculations, 5-point component matrix checks, and instant E-IDS certificate PDF generation.
                </p>

                <div class="pt-4 flex flex-wrap items-center gap-4 justify-center lg:justify-start">
                    @auth
                        <a href="{{ url('/dashboard') }}"
                           class="min-h-[48px] px-8 py-3.5 bg-eids-accent text-white text-base font-extrabold rounded-xl hover:bg-emerald-600 transition shadow-lg inline-flex items-center gap-2">
                            Go to Inspection Dashboard &rarr;
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                           class="min-h-[48px] px-8 py-3.5 bg-white text-eids-primary text-base font-extrabold rounded-xl hover:bg-gray-100 transition shadow-lg inline-flex items-center gap-2">
                            Sign In to Portal &rarr;
                        </a>
                    @endauth
                    <a href="#features" class="min-h-[48px] px-6 py-3.5 border border-white/20 text-white text-sm font-bold rounded-xl hover:bg-white/10 transition inline-flex items-center gap-2">
                        System Features
                    </a>
                </div>
            </div>

            <div class="lg:col-span-5 flex justify-center">
                <div class="bg-white/5 border border-white/15 p-8 rounded-3xl backdrop-blur-md shadow-2xl text-center max-w-md w-full">
                    <div class="w-20 h-20 rounded-2xl bg-eids-accent/30 border border-eids-accent/50 text-eids-light flex items-center justify-center mx-auto mb-5 shadow-inner">
                        <span class="material-symbols-outlined text-4xl">analytics</span>
                    </div>
                    <div class="text-xs uppercase tracking-widest text-eids-light font-bold mb-1">E-IDS Scoring Engine</div>
                    <div class="text-4xl font-extrabold text-white mb-2">Native CIS 7 Rules</div>
                    <p class="text-white/70 text-xs leading-relaxed font-medium">
                        Auto-calculates S_comp weighted architectural component pass rates, fixed M&amp;E &amp; external scores, and GOOD / MODERATE / WEAK rating thresholds.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- Core System Features Grid --}}
    <section id="features" class="py-16 lg:py-24 max-w-7xl mx-auto px-6 flex-1">
        <div class="text-center max-w-2xl mx-auto mb-16">
            <span class="text-eids-accent text-xs font-extrabold uppercase tracking-widest">Built for Field &amp; Office</span>
            <h2 class="text-2xl sm:text-4xl font-extrabold text-gray-900 mt-2 tracking-tight">Structured E-IDS Inspection Workflow</h2>
            <p class="text-gray-600 text-sm mt-3 font-medium">Built specifically for government inspectors, private inspection firms, and developer QC teams in Malaysia.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {{-- Feature 1 --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-6 flex flex-col justify-between">
                <div>
                    <div class="w-12 h-12 rounded-xl bg-eids-primary/10 border border-eids-primary/20 flex items-center justify-center text-eids-primary mb-4">
                        <span class="material-symbols-outlined text-2xl">calculate</span>
                    </div>
                    <h3 class="font-extrabold text-gray-900 text-base mb-2">Automated Sample Formula</h3>
                    <p class="text-xs text-gray-600 leading-relaxed font-medium">Calculates required sample units <code class="bg-gray-100 px-1 py-0.5 rounded font-mono">N = ceil(GFA ÷ 60)</code> instantly from project Gross Floor Area.</p>
                </div>
            </div>

            {{-- Feature 2 --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-6 flex flex-col justify-between">
                <div>
                    <div class="w-12 h-12 rounded-xl bg-eids-accent/10 border border-eids-accent/20 flex items-center justify-center text-eids-accent mb-4">
                        <span class="material-symbols-outlined text-2xl">checklist</span>
                    </div>
                    <h3 class="font-extrabold text-gray-900 text-base mb-2">5-Point Check Matrix</h3>
                    <p class="text-xs text-gray-600 leading-relaxed font-medium">Assesses Finishing, Hollow sound, Levelling tolerance (mm), Joint gap (mm), and Crack status for each component cell.</p>
                </div>
            </div>

            {{-- Feature 3 --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-6 flex flex-col justify-between">
                <div>
                    <div class="w-12 h-12 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-700 mb-4">
                        <span class="material-symbols-outlined text-2xl">warning</span>
                    </div>
                    <h3 class="font-extrabold text-gray-900 text-base mb-2">Auto-Defect Generation</h3>
                    <p class="text-xs text-gray-600 leading-relaxed font-medium">FAIL check results automatically spawn defect records with photo attachments and severity tracking.</p>
                </div>
            </div>

            {{-- Feature 4 --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-6 flex flex-col justify-between">
                <div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-700 mb-4">
                        <span class="material-symbols-outlined text-2xl">description</span>
                    </div>
                    <h3 class="font-extrabold text-gray-900 text-base mb-2">Formal PDF Certificates</h3>
                    <p class="text-xs text-gray-600 leading-relaxed font-medium">Exports printable A4 E-IDS score certificates and defect logs ready for client or regulatory sign-off.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="bg-eids-primary text-white border-t border-white/10 py-8">
        <div class="max-w-7xl mx-auto px-6 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-white/60 font-medium">
            <div class="flex items-center gap-2">
                <span class="font-bold text-white">E-IDS v2</span>
                <span>·</span>
                <span>Politeknik Merlimau Melaka</span>
                <span>·</span>
                <span>Department of Civil Engineering</span>
            </div>
            <div>&copy; {{ date('Y') }} E-IDS. All rights reserved.</div>
        </div>
    </footer>

</body>
</html>
