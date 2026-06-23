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

        <!-- Tab Navigation -->
        <nav class="flex space-x-4 mb-8 border-b border-white/10" aria-label="Tabs">
            <button @click="activeTab = 'live'" :class="activeTab === 'live' ? 'border-blue-500 text-blue-400' : 'border-transparent text-gray-400 hover:text-gray-300 hover:border-white/20'" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                Live Traffic
            </button>
            <button @click="activeTab = 'server'; runScan('server')" :class="activeTab === 'server' ? 'border-blue-500 text-blue-400' : 'border-transparent text-gray-400 hover:text-gray-300 hover:border-white/20'" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Server Scanner
            </button>
            <button @click="activeTab = 'database'; runScan('database')" :class="activeTab === 'database' ? 'border-blue-500 text-blue-400' : 'border-transparent text-gray-400 hover:text-gray-300 hover:border-white/20'" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Database Scanner
            </button>
            <button @click="activeTab = 'backend'; runScan('backend')" :class="activeTab === 'backend' ? 'border-blue-500 text-blue-400' : 'border-transparent text-gray-400 hover:text-gray-300 hover:border-white/20'" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Backend Scanner
            </button>
            <button @click="activeTab = 'frontend'; runScan('frontend')" :class="activeTab === 'frontend' ? 'border-blue-500 text-blue-400' : 'border-transparent text-gray-400 hover:text-gray-300 hover:border-white/20'" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Frontend Scanner
            </button>
        </nav>

        <!-- Live Traffic Content Area -->
        <div x-show="activeTab === 'live'" class="bg-white/5 border border-white/10 rounded-2xl p-6 backdrop-blur-sm shadow-xl">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-semibold flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    Recent Requests
                </h2>
                <div>
                    <input type="text" x-model="searchQuery" placeholder="Filter by path, method, or status..." class="bg-black/50 border border-white/10 rounded-lg px-4 py-2 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 w-64">
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-white/10 text-gray-400 text-sm">
                            <th class="pb-3 font-medium">Time</th>
                            <th class="pb-3 font-medium">Path</th>
                            <th class="pb-3 font-medium">Method</th>
                            <th class="pb-3 font-medium text-right">Status</th>
                            <th class="pb-3 font-medium text-right">Duration</th>
                            <th class="pb-3 font-medium text-right">DB Time</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm">
                        <template x-for="req in filteredRequests" :key="req.request_id">
                            <tr @click="openDetails(req.request_id)" class="border-b border-white/5 hover:bg-white/10 transition-colors group cursor-pointer">
                                <td class="py-4 text-gray-400 whitespace-nowrap text-xs" x-text="new Date(req.created_at || req.timestamp).toLocaleString()"></td>
                                <td class="py-4 font-mono text-gray-300 group-hover:text-white">
                                    <div x-text="req.url"></div>
                                    <div x-show="req.parent_request_id" class="text-xs text-purple-400 flex items-center gap-1 mt-1 font-sans">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                        </svg>
                                        <span>Internal call from</span>
                                        <span class="underline font-mono text-purple-300" x-text="req.parent_method + ' ' + (req.parent_url ? req.parent_url.replace(/^https?:\/\/[^\/]+/, '') : 'parent')"></span>
                                    </div>
                                </td>
                                <td class="py-4">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-1 bg-white/10 text-xs rounded-md text-gray-300" x-text="req.method"></span>
                                        <span x-show="req.request_type === 'AJAX / API'" class="px-2 py-1 bg-blue-500/20 text-blue-400 text-xs rounded-md border border-blue-500/30">AJAX</span>
                                        <span x-show="req.parent_request_id" class="px-2 py-1 bg-purple-500/20 text-purple-400 text-xs rounded-md border border-purple-500/30">Internal</span>
                                        <span x-show="!req.parent_request_id" class="px-2 py-1 bg-emerald-500/20 text-emerald-400 text-xs rounded-md border border-emerald-500/30">Direct</span>
                                    </div>
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
                            <td colspan="6" class="py-8 text-center text-gray-500">Loading telemetry data...</td>
                        </tr>
                        <tr x-show="!loading && filteredRequests.length === 0">
                            <td colspan="6" class="py-8 text-center text-gray-500">No requests found matching your filter.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Scanner Content Area -->
        <div x-show="activeTab !== 'live'" class="bg-white/5 border border-white/10 rounded-2xl p-6 backdrop-blur-sm shadow-xl" style="display: none;">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-semibold capitalize" x-text="activeTab + ' Vulnerability Scan'"></h2>
                <button @click="runScan(activeTab)" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition-colors">
                    Rescan Now
                </button>
            </div>

            <div x-show="scanning" class="py-12 text-center text-gray-400 animate-pulse">
                <svg class="animate-spin h-8 w-8 mx-auto text-blue-500 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Scanning <span x-text="activeTab"></span> configuration and codebase...
            </div>

            <div x-show="!scanning" class="space-y-4">
                <template x-for="(vuln, idx) in scanResults[activeTab] || []" :key="idx">
                    <div class="p-4 rounded-lg border flex gap-4 items-start"
                         :class="{
                             'bg-red-500/10 border-red-500/30': vuln.severity === 'critical',
                             'bg-orange-500/10 border-orange-500/30': vuln.severity === 'high' || vuln.severity === 'warning',
                             'bg-green-500/10 border-green-500/30': vuln.severity === 'success'
                         }">
                        
                        <div class="mt-1" x-show="vuln.severity === 'critical' || vuln.severity === 'high'">
                            <svg class="w-6 h-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                        </div>
                        <div class="mt-1" x-show="vuln.severity === 'warning'">
                            <svg class="w-6 h-6 text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <div class="mt-1" x-show="vuln.severity === 'success'">
                            <svg class="w-6 h-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>

                        <div>
                            <h5 class="font-semibold text-white text-md mb-1" x-text="vuln.title"></h5>
                            <p class="text-sm text-gray-300 mb-3" x-text="vuln.description"></p>
                            
                            <div x-show="vuln.solution" class="bg-black/30 rounded px-3 py-2 border border-white/5">
                                <span class="text-xs font-bold uppercase tracking-wider block mb-1"
                                      :class="{
                                          'text-red-300': vuln.severity === 'critical',
                                          'text-orange-300': vuln.severity === 'high' || vuln.severity === 'warning',
                                          'text-green-300': vuln.severity === 'success'
                                      }">Remediation / Solution</span>
                                <p class="text-sm font-mono text-gray-200" x-text="vuln.solution"></p>
                            </div>
                        </div>
                    </div>
                </template>
                
                <div x-show="(scanResults[activeTab] || []).length === 0 && !scanning" class="text-center py-12 text-gray-500">
                    Run a scan to analyze <span x-text="activeTab"></span> vulnerabilities.
                </div>
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
                    <p class="text-gray-400 animate-pulse text-center py-10">Fetching deep metrics and generating insights...</p>
                </div>
                <div class="px-6 py-6 text-gray-300" x-show="!loadingDetails && requestDetails">
                    
                    <!-- Actionable Insights Section -->
                    <template x-if="requestDetails?.insights && requestDetails.insights.length > 0">
                        <div class="mb-8">
                            <h4 class="font-semibold text-white mb-4 border-b border-white/10 pb-2 flex items-center gap-2">
                                <svg class="w-5 h-5 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                </svg>
                                Actionable Insights
                            </h4>
                            <div class="space-y-4">
                                <template x-for="(insight, idx) in requestDetails.insights">
                                    <div class="p-4 rounded-lg border flex gap-4 items-start"
                                         :class="{
                                             'bg-red-500/10 border-red-500/30': insight.type === 'critical',
                                             'bg-orange-500/10 border-orange-500/30': insight.type === 'warning',
                                             'bg-green-500/10 border-green-500/30': insight.type === 'success'
                                         }">
                                        
                                        <!-- Icon based on type -->
                                        <div class="mt-1" x-show="insight.type === 'critical'">
                                            <svg class="w-6 h-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                        </div>
                                        <div class="mt-1" x-show="insight.type === 'warning'">
                                            <svg class="w-6 h-6 text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        </div>
                                        <div class="mt-1" x-show="insight.type === 'success'">
                                            <svg class="w-6 h-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        </div>

                                        <div>
                                            <h5 class="font-semibold text-white text-md mb-1" x-text="insight.title"></h5>
                                            <p class="text-sm text-gray-300 mb-3" x-text="insight.description"></p>
                                            
                                            <div class="bg-black/30 rounded px-3 py-2 border border-white/5">
                                                <span class="text-xs font-bold uppercase tracking-wider block mb-1"
                                                      :class="{
                                                          'text-red-300': insight.type === 'critical',
                                                          'text-orange-300': insight.type === 'warning',
                                                          'text-green-300': insight.type === 'success'
                                                      }">Recommended Solution</span>
                                                <p class="text-sm font-mono text-gray-200" x-text="insight.solution"></p>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    <!-- Trace Relationships Section -->
                    <template x-if="requestDetails?.parent_request_id || (requestDetails?.children && requestDetails.children.length > 0)">
                        <div class="mb-8">
                            <h4 class="font-semibold text-white mb-4 border-b border-white/10 pb-2 flex items-center gap-2">
                                <svg class="w-5 h-5 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                                Trace Relationships
                            </h4>
                            <div class="space-y-4">
                                <!-- Parent Request -->
                                <template x-if="requestDetails?.parent_request_id">
                                    <div class="bg-purple-950/20 border border-purple-500/20 rounded-lg p-4 flex justify-between items-center">
                                        <div>
                                            <span class="text-xs text-purple-400 font-bold uppercase tracking-wider block mb-1">Parent Request (Caller)</span>
                                            <div class="flex items-center gap-2">
                                                <span class="px-2 py-0.5 bg-purple-500/20 text-purple-300 text-xs rounded font-mono" x-text="requestDetails.parent_method"></span>
                                                <span class="font-mono text-sm break-all text-white" x-text="requestDetails.parent_url"></span>
                                            </div>
                                        </div>
                                        <button @click="openDetails(requestDetails.parent_request_id)" class="px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold rounded transition-colors whitespace-nowrap">
                                            View Parent
                                        </button>
                                    </div>
                                </template>

                                <!-- Child Requests (Sub-requests) -->
                                <template x-if="requestDetails?.children && requestDetails.children.length > 0">
                                    <div class="bg-blue-950/20 border border-blue-500/20 rounded-lg p-4">
                                        <span class="text-xs text-blue-400 font-bold uppercase tracking-wider block mb-3">Internal API Sub-requests (Called by this page)</span>
                                        <div class="space-y-2">
                                            <template x-for="child in requestDetails.children" :key="child.request_id">
                                                <div class="flex justify-between items-center bg-black/40 border border-white/5 rounded p-3 hover:border-white/10 transition-all">
                                                    <div class="flex items-center gap-3 overflow-hidden">
                                                        <span class="px-2 py-0.5 bg-blue-500/20 text-blue-300 text-xs rounded font-mono" x-text="child.method"></span>
                                                        <span class="font-mono text-sm truncate text-gray-300" x-text="child.url"></span>
                                                    </div>
                                                    <div class="flex items-center gap-4 ml-4">
                                                        <span class="px-2 py-0.5 rounded text-xs font-medium" 
                                                              :class="child.status >= 500 ? 'bg-red-500/20 text-red-400' : (child.status >= 400 ? 'bg-orange-500/20 text-orange-400' : 'bg-green-500/20 text-green-400')"
                                                              x-text="child.status"></span>
                                                        <span class="text-orange-400 font-mono text-sm whitespace-nowrap" x-text="Math.round(child.total_duration * 1000) + 'ms'"></span>
                                                        <button @click="openDetails(child.request_id)" class="px-2.5 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded transition-colors whitespace-nowrap">
                                                            Inspect
                                                        </button>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    <h4 class="font-semibold text-white mb-4 border-b border-white/10 pb-2">Request Overview</h4>
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

                    <h4 class="font-semibold text-white mb-4 border-b border-white/10 pb-2">Resource Usage</h4>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                        <div class="bg-black/50 rounded-lg p-4 border border-white/5">
                            <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Peak Memory</p>
                            <p class="text-lg font-bold text-blue-400" x-text="requestDetails?.metrics?.memory?.peak_memory ? (requestDetails.metrics.memory.peak_memory / 1024 / 1024).toFixed(2) + ' MB' : 'N/A'"></p>
                        </div>
                        <div class="bg-black/50 rounded-lg p-4 border border-white/5">
                            <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Memory Allocation</p>
                            <p class="text-lg font-bold text-gray-300" x-text="requestDetails?.metrics?.memory?.start_memory ? ((requestDetails.metrics.memory.end_memory - requestDetails.metrics.memory.start_memory) / 1024 / 1024).toFixed(2) + ' MB' : 'N/A'"></p>
                        </div>
                        <div class="bg-black/50 rounded-lg p-4 border border-white/5">
                            <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">PHP CPU Time</p>
                            <p class="text-lg font-bold text-emerald-400" x-text="requestDetails?.metrics?.cpu?.total_cpu_ms ? Math.round(requestDetails.metrics.cpu.total_cpu_ms) + 'ms' : 'N/A'"></p>
                        </div>
                        <div class="bg-black/50 rounded-lg p-4 border border-white/5">
                            <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">1m Server Load</p>
                            <p class="text-lg font-bold text-purple-400" x-text="requestDetails?.metrics?.cpu?.load_avg_1m !== undefined ? requestDetails.metrics.cpu.load_avg_1m.toFixed(2) : 'N/A'"></p>
                        </div>
                    </div>

                    <h4 class="font-semibold text-white mb-4 border-b border-white/10 pb-2">Time Breakdown</h4>
                    <div class="bg-black/50 rounded-lg border border-white/5 overflow-hidden mb-8 p-4">
                        <div class="flex items-center justify-between mb-2 text-sm">
                            <span class="text-gray-400">Framework Boot</span>
                            <span class="font-mono text-blue-400" x-text="Math.round((requestDetails?.metrics?.request?.boot_duration || 0) * 1000) + 'ms'"></span>
                        </div>
                        <div class="w-full bg-white/5 rounded-full h-2 mb-4">
                            <div class="bg-blue-400 h-2 rounded-full" :style="'width: ' + Math.min(100, Math.max(1, ((requestDetails?.metrics?.request?.boot_duration || 0) / requestDetails?.total_duration) * 100)) + '%'"></div>
                        </div>

                        <div class="flex items-center justify-between mb-2 text-sm">
                            <span class="text-gray-400">Database Queries</span>
                            <span class="font-mono text-orange-400" x-text="Math.round(requestDetails?.metrics?.database?.total_time || 0) + 'ms'"></span>
                        </div>
                        <div class="w-full bg-white/5 rounded-full h-2 mb-4">
                            <div class="bg-orange-400 h-2 rounded-full" :style="'width: ' + Math.min(100, Math.max(1, ((requestDetails?.metrics?.database?.total_time || 0) / 1000 / requestDetails?.total_duration) * 100)) + '%'"></div>
                        </div>

                        <div class="flex items-center justify-between mb-2 text-sm">
                            <span class="text-gray-400">Application Execution (Controllers, APIs, Views)</span>
                            <span class="font-mono text-purple-400" x-text="Math.max(0, Math.round(requestDetails?.total_duration * 1000) - Math.round((requestDetails?.metrics?.request?.boot_duration || 0) * 1000) - Math.round(requestDetails?.metrics?.database?.total_time || 0)) + 'ms'"></span>
                        </div>
                        <div class="w-full bg-white/5 rounded-full h-2">
                            <div class="bg-purple-400 h-2 rounded-full" :style="'width: ' + Math.min(100, Math.max(1, (Math.max(0, requestDetails?.total_duration - (requestDetails?.metrics?.request?.boot_duration || 0) - ((requestDetails?.metrics?.database?.total_time || 0) / 1000)) / requestDetails?.total_duration) * 100)) + '%'"></div>
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
                        <div class="p-4 max-h-96 overflow-y-auto font-mono text-xs text-blue-300 space-y-3">
                            <template x-for="(query, idx) in (requestDetails?.metrics?.database?.queries || [])">
                                <div class="border-b border-white/5 pb-3" x-data="{ showExplain: false }">
                                    <div class="text-orange-400 float-right" x-text="query.time.toFixed(2) + 'ms'"></div>
                                    <div x-text="query.sql" class="mb-1 whitespace-pre-wrap break-all"></div>
                                    <div class="text-gray-500 break-all mb-2" x-show="query.bindings && query.bindings.length" x-text="'Bindings: ' + JSON.stringify(query.bindings)"></div>
                                    
                                    <!-- Explain toggler -->
                                    <template x-if="query.explain">
                                        <div class="mt-2">
                                            <button @click="showExplain = !showExplain" class="px-2 py-1 bg-white/10 hover:bg-white/20 text-[10px] rounded text-gray-300 font-semibold transition-colors flex items-center gap-1 focus:outline-none">
                                                <svg class="w-3 h-3 transition-transform" :class="showExplain ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7" />
                                                </svg>
                                                Explain Plan
                                            </button>
                                            
                                            <div x-show="showExplain" x-transition class="mt-2 bg-black/60 rounded border border-white/10 p-3 overflow-x-auto text-[10px] text-gray-300 w-full">
                                                <template x-if="query.explain.error">
                                                    <div class="text-red-400 font-mono" x-text="'Error running EXPLAIN: ' + query.explain.error"></div>
                                                </template>
                                                
                                                <template x-if="!query.explain.error">
                                                    <table class="w-full text-left font-mono border-collapse">
                                                        <thead>
                                                            <tr class="border-b border-white/20 text-gray-400">
                                                                <template x-for="key in Object.keys(query.explain[0] || {})">
                                                                    <th class="pb-1 pr-4 font-bold capitalize whitespace-nowrap" x-text="key"></th>
                                                                </template>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <template x-for="row in query.explain">
                                                                <tr class="border-b border-white/5 hover:bg-white/5">
                                                                    <template x-for="val in Object.values(row)">
                                                                        <td class="py-1 pr-4 whitespace-nowrap text-gray-300" x-text="val !== null ? val : 'NULL'"></td>
                                                                    </template>
                                                                </tr>
                                                            </template>
                                                        </tbody>
                                                    </table>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                            <div x-show="!requestDetails?.metrics?.database?.queries?.length" class="text-gray-500">No database queries executed.</div>
                        </div>
                    </div>

                    <h4 class="font-semibold text-white mb-4 border-b border-white/10 pb-2">Outgoing API & HTTP Calls</h4>
                    <div class="bg-black/50 rounded-lg border border-white/5 overflow-hidden mb-8">
                        <div class="p-4 flex gap-8 bg-white/5 border-b border-white/5">
                            <div>
                                <span class="text-sm text-gray-400">Total API Requests:</span>
                                <span class="ml-2 font-bold text-white" x-text="requestDetails?.metrics?.api?.total_queries || 0"></span>
                            </div>
                            <div>
                                <span class="text-sm text-gray-400">Total Time:</span>
                                <span class="ml-2 font-bold text-orange-400" x-text="Math.round(requestDetails?.metrics?.api?.total_time || 0) + 'ms'"></span>
                            </div>
                        </div>
                        <div class="p-4 max-h-64 overflow-y-auto font-mono text-xs text-blue-300 space-y-3">
                            <template x-for="(apiCall, idx) in (requestDetails?.metrics?.api?.requests || [])">
                                <div class="border-b border-white/5 pb-2 flex justify-between items-start">
                                    <div class="overflow-hidden mr-4">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="px-1.5 py-0.5 bg-white/10 text-[10px] rounded text-gray-300 font-mono" x-text="apiCall.method"></span>
                                            <span class="font-semibold text-gray-200 break-all" x-text="apiCall.url"></span>
                                        </div>
                                        <div class="text-[10px] text-gray-500">
                                            <span x-show="apiCall.is_internal" class="text-purple-400 font-sans">Internal Call</span>
                                            <span x-show="!apiCall.is_internal" class="text-gray-400 font-sans">External Call</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 whitespace-nowrap">
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-medium"
                                              :class="apiCall.status === 'failed' || apiCall.status >= 500 ? 'bg-red-500/20 text-red-400' : (apiCall.status >= 400 ? 'bg-orange-500/20 text-orange-400' : 'bg-green-500/20 text-green-400')"
                                              x-text="apiCall.status"></span>
                                        <span class="text-orange-400 font-bold" x-text="Math.round(apiCall.duration) + 'ms'"></span>
                                    </div>
                                </div>
                            </template>
                            <div x-show="!requestDetails?.metrics?.api?.requests?.length" class="text-gray-500">No outgoing HTTP/API calls made by this request.</div>
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
                activeTab: 'live',
                searchQuery: '',
                requests: [],
                loading: true,
                selectedRequest: null,
                requestDetails: null,
                loadingDetails: false,
                scanning: false,
                scanResults: {
                    server: [],
                    database: [],
                    backend: [],
                    frontend: []
                },
                get filteredRequests() {
                    if (this.searchQuery === '') {
                        return this.requests;
                    }
                    const q = this.searchQuery.toLowerCase();
                    return this.requests.filter(req => {
                        return (req.url && req.url.toLowerCase().includes(q)) || 
                               (req.method && req.method.toLowerCase().includes(q)) ||
                               (req.status && req.status.toString().includes(q));
                    });
                },
                getApiUrl(endpoint) {
                    let path = window.location.pathname;
                    if (!path.endsWith('/')) {
                        path += '/';
                    }
                    return path + 'api/' + endpoint;
                },
                init() {
                    this.fetchData();
                    setInterval(() => {
                        if (!this.selectedRequest && this.activeTab === 'live') {
                            this.fetchData();
                        }
                    }, 5000);
                },
                fetchData() {
                    fetch(this.getApiUrl('requests'))
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
                    
                    fetch(this.getApiUrl('requests/' + id))
                        .then(res => res.json())
                        .then(data => {
                            this.requestDetails = data;
                            this.loadingDetails = false;
                        })
                        .catch(err => {
                            console.error('Failed to load details', err);
                            this.loadingDetails = false;
                        });
                },
                runScan(type) {
                    this.scanning = true;
                    this.scanResults[type] = [];
                    
                    fetch(this.getApiUrl('scan/' + type))
                        .then(res => {
                            if (!res.ok) throw new Error('Network response was not ok');
                            return res.json();
                        })
                        .then(data => {
                            this.scanResults[type] = data.data;
                            this.scanning = false;
                        })
                        .catch(err => {
                            console.error('Scan failed', err);
                            this.scanResults[type] = [{
                                severity: 'critical',
                                title: 'Scanner Failed to Run',
                                description: 'The scanner could not complete its analysis. This could be due to a server error or a missing route.',
                                solution: 'Try running `php artisan route:clear` and refreshing the page.'
                            }];
                            this.scanning = false;
                        });
                }
            }
        }
    </script>
</body>
</html>
