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
<body class="min-h-screen p-8 md:p-24 relative overflow-x-hidden text-gray-200" x-data="dashboardData()">
    <!-- Background decoration -->
    <div class="fixed top-[-10%] left-[-10%] w-[40%] h-[40%] rounded-full bg-blue-600/20 blur-[120px] pointer-events-none"></div>
    <div class="fixed bottom-[-10%] right-[-10%] w-[40%] h-[40%] rounded-full bg-purple-600/20 blur-[120px] pointer-events-none"></div>
    
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
        <div class="bg-white/5 border border-white/10 rounded-2xl p-6 backdrop-blur-sm shadow-xl">
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
                        <template x-for="req in requests" :key="req.request_id">
                            <tr @click="openDetails(req.request_id)" class="border-b border-white/5 hover:bg-white/10 transition-colors group cursor-pointer">
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

    <!-- Details Modal -->
    <div x-show="selectedRequest" style="display: none" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            
            <div x-show="selectedRequest" x-transition.opacity class="fixed inset-0 bg-black bg-opacity-75 transition-opacity" @click="selectedRequest = null" aria-hidden="true"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="selectedRequest" x-transition class="inline-block align-bottom bg-[#121212] rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full border border-white/10">
                <div class="px-6 py-4 border-b border-white/10 flex justify-between items-center bg-white/5">
                    <h3 class="text-xl font-medium leading-6 text-white" id="modal-title">
                        Request Profile
                    </h3>
                    <button @click="selectedRequest = null" class="text-gray-400 hover:text-white">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="px-6 py-6" x-show="loadingDetails">
                    <p class="text-gray-400 animate-pulse text-center py-10">Fetching deep metrics...</p>
                </div>
                <div class="px-6 py-6 text-gray-300" x-show="!loadingDetails && requestDetails">
                    
                    <div class="grid grid-cols-2 gap-6 mb-8">
                        <div class="bg-black/50 rounded-lg p-4 border border-white/5">
                            <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Target URL</p>
                            <p class="font-mono text-sm break-all" x-text="requestDetails?.url"></p>
                        </div>
                        <div class="bg-black/50 rounded-lg p-4 border border-white/5 flex justify-between">
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Total Duration</p>
                                <p class="text-2xl font-bold text-orange-400" x-text="Math.round(requestDetails?.total_duration * 1000) + 'ms'"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Method</p>
                                <p class="text-lg font-bold" x-text="requestDetails?.method"></p>
                            </div>
                        </div>
                    </div>

                    <h4 class="font-semibold text-white mb-4 border-b border-white/10 pb-2">Database Analysis</h4>
                    <div class="bg-black/50 rounded-lg border border-white/5 overflow-hidden mb-8">
                        <div class="p-4 flex gap-8 bg-white/5 border-b border-white/5">
                            <div>
                                <span class="text-sm text-gray-400">Total Queries:</span>
                                <span class="ml-2 font-bold text-white" x-text="requestDetails?.metrics?.database?.total_queries || 0"></span>
                            </div>
                            <div>
                                <span class="text-sm text-gray-400">Total Time:</span>
                                <span class="ml-2 font-bold text-orange-400" x-text="(requestDetails?.metrics?.database?.total_time || 0).toFixed(2) + 'ms'"></span>
                            </div>
                        </div>
                        <div class="p-4 max-h-64 overflow-y-auto font-mono text-xs text-blue-300 space-y-3">
                            <template x-for="(query, idx) in (requestDetails?.metrics?.database?.queries || [])">
                                <div class="border-b border-white/5 pb-2">
                                    <div class="text-orange-400 float-right" x-text="query.time.toFixed(2) + 'ms'"></div>
                                    <div x-text="query.sql" class="mb-1"></div>
                                    <div class="text-gray-500" x-show="query.bindings.length" x-text="'Bindings: ' + JSON.stringify(query.bindings)"></div>
                                </div>
                            </template>
                            <div x-show="!requestDetails?.metrics?.database?.queries?.length" class="text-gray-500">No database queries executed.</div>
                        </div>
                    </div>

                    <h4 class="font-semibold text-white mb-4 border-b border-white/10 pb-2">Request Context</h4>
                    <div class="grid grid-cols-2 gap-4 text-sm font-mono bg-black/50 p-4 rounded-lg border border-white/5">
                        <div>
                            <span class="text-gray-500">IP Address:</span>
                            <span class="ml-2" x-text="requestDetails?.metrics?.request?.ip"></span>
                        </div>
                        <div>
                            <span class="text-gray-500">Payload Size:</span>
                            <span class="ml-2" x-text="requestDetails?.metrics?.request?.payload_size + ' bytes'"></span>
                        </div>
                        <div class="col-span-2 mt-2 pt-2 border-t border-white/5 text-gray-400 text-xs overflow-hidden break-all">
                            User Agent: <span x-text="requestDetails?.metrics?.request?.headers['user-agent']"></span>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script>
        function dashboardData() {
            return {
                requests: [],
                loading: true,
                selectedRequest: null,
                requestDetails: null,
                loadingDetails: false,
                init() {
                    this.fetchData();
                    setInterval(() => {
                        if (!this.selectedRequest) {
                            this.fetchData();
                        }
                    }, 5000);
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
                },
                openDetails(id) {
                    this.selectedRequest = id;
                    this.loadingDetails = true;
                    this.requestDetails = null;
                    
                    fetch('{{ url(config("observatory.route_prefix") . "/api/requests") }}/' + id)
                        .then(res => res.json())
                        .then(data => {
                            this.requestDetails = data;
                            this.loadingDetails = false;
                        })
                        .catch(err => {
                            console.error('Failed to load details', err);
                            this.loadingDetails = false;
                        });
                }
            }
        }
    </script>
</body>
</html>
