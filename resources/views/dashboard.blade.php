<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance Observatory</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body {
            background-color: #0a0a0a;
            color: #ededed;
            font-family: 'Inter', sans-serif;
            background-image: radial-gradient(circle at 50% 0%, rgba(59, 130, 246, 0.15) 0%, transparent 50%),
                              radial-gradient(circle at 100% 100%, rgba(139, 92, 246, 0.15) 0%, transparent 50%);
            background-attachment: fixed;
        }
    </style>
</head>
<body class="min-h-screen p-8 md:p-24 relative overflow-hidden" x-data="dashboardData()">
    <!-- Background decoration -->
    <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] rounded-full bg-blue-600/20 blur-[120px] pointer-events-none"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] rounded-full bg-purple-600/20 blur-[120px] pointer-events-none"></div>
    
    <div class="max-w-7xl mx-auto relative z-10">
        <header class="flex justify-between items-center mb-12 border-b border-white/10 pb-6">
            <div>
                <h1 class="text-4xl font-extrabold bg-clip-text text-transparent bg-gradient-to-r from-blue-400 to-purple-400 tracking-tight">
                    Performance Observatory
                </h1>
                <p class="text-gray-400 mt-2 text-lg">Real-time application telemetry & bottlenecks.</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2 bg-white/5 border border-white/10 px-4 py-2 rounded-full backdrop-blur-md">
                    <span class="w-2 h-2 rounded-full bg-green-500 shadow-[0_0_10px_rgba(34,197,94,0.8)] animate-pulse"></span>
                    <span class="text-sm font-medium">System Online</span>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <div class="bg-white/5 border border-white/10 rounded-2xl p-6 backdrop-blur-sm">
            <h2 class="text-xl font-semibold mb-6 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                Recent Requests
            </h2>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-white/10 text-gray-400 text-sm">
                            <th class="pb-3 font-medium">Path</th>
                            <th class="pb-3 font-medium">Method</th>
                            <th class="pb-3 font-medium text-right">Status</th>
                            <th class="pb-3 font-medium text-right">Duration</th>
                            <th class="pb-3 font-medium text-right">DB Time</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm">
                        <template x-for="req in requests" :key="req.id">
                            <tr class="border-b border-white/5 hover:bg-white/5 transition-colors group cursor-pointer">
                                <td class="py-4 font-mono text-gray-300 group-hover:text-white" x-text="req.url"></td>
                                <td class="py-4">
                                    <span class="px-2 py-1 bg-white/10 text-xs rounded-md text-gray-300" x-text="req.method"></span>
                                </td>
                                <td class="py-4 text-right">
                                    <span class="px-2 py-1 rounded-md text-xs font-medium" 
                                          :class="req.status >= 500 ? 'bg-red-500/20 text-red-400' : (req.status >= 400 ? 'bg-orange-500/20 text-orange-400' : 'bg-green-500/20 text-green-400')"
                                          x-text="req.status"></span>
                                </td>
                                <td class="py-4 text-right font-medium text-orange-400" x-text="Math.round(req.total_duration * 1000) + 'ms'"></td>
                                <td class="py-4 text-right text-gray-400" x-text="Math.round(req.db_time) + 'ms'"></td>
                            </tr>
                        </template>
                        <tr x-show="loading" class="animate-pulse">
                            <td colspan="5" class="py-8 text-center text-gray-500">Loading telemetry data...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function dashboardData() {
            return {
                requests: [],
                loading: true,
                init() {
                    this.fetchData();
                    // Poll every 5 seconds
                    setInterval(() => this.fetchData(), 5000);
                },
                fetchData() {
                    fetch('{{ url(config("observatory.route_prefix") . "/api/requests") }}')
                        .then(res => res.json())
                        .then(data => {
                            this.requests = data.data;
                            this.loading = false;
                        })
                        .catch(err => {
                            console.error('Failed to fetch data', err);
                            this.loading = false;
                        });
                }
            }
        }
    </script>
</body>
</html>
