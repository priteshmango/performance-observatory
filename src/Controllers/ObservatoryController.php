<?php

namespace Performance\Observatory\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class ObservatoryController extends Controller
{
    public function index(Request $request)
    {
        $connection = config('observatory.storage.connection');
        
        $requests = DB::connection($connection)
            ->table('observatory_requests')
            ->orderBy('created_at', 'desc')
            ->paginate(50);
            
        // Process metrics payload to return summarized data for the dashboard list
        $requests->getCollection()->transform(function ($item) {
            $metrics = json_decode($item->metrics_payload, true);
            $item->status = $metrics['request']['status'] ?? 200;
            $item->db_time = $metrics['database']['total_time'] ?? 0;
            $item->db_queries = $metrics['database']['total_queries'] ?? 0;
            unset($item->metrics_payload); // Don't send full payload in list view
            return $item;
        });

        return response()->json($requests);
    }

    public function show($id)
    {
        $connection = config('observatory.storage.connection');
        
        $request = DB::connection($connection)
            ->table('observatory_requests')
            ->where('request_id', $id)
            ->first();

        if (!$request) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $request->metrics = json_decode($request->metrics_payload, true);
        unset($request->metrics_payload);

        // Generate actionable insights dynamically
        $engine = new \Performance\Observatory\Engines\RootCauseEngine();
        $request->insights = $engine->analyze($request->metrics, (float) $request->total_duration);

        return response()->json($request);
    }

    public function scanServer(\Performance\Observatory\Engines\StaticAnalysisEngine $engine)
    {
        return response()->json(['data' => $engine->scanServer()]);
    }

    public function scanDatabase(\Performance\Observatory\Engines\StaticAnalysisEngine $engine)
    {
        return response()->json(['data' => $engine->scanDatabase()]);
    }

    public function scanBackend(\Performance\Observatory\Engines\StaticAnalysisEngine $engine)
    {
        return response()->json(['data' => $engine->scanBackend()]);
    }

    public function scanFrontend(\Performance\Observatory\Engines\StaticAnalysisEngine $engine)
    {
        return response()->json(['data' => $engine->scanFrontend()]);
    }

    public function storeFrontendMetrics(Request $request)
    {
        $payload = $request->validate([
            'request_id' => 'required|string',
            'metrics' => 'required|array',
        ]);

        $connection = config('observatory.storage.connection');
        
        $record = DB::connection($connection)
            ->table('observatory_requests')
            ->where('request_id', $payload['request_id'])
            ->first();

        if ($record) {
            $existingMetrics = json_decode($record->metrics_payload, true);
            $existingMetrics['frontend'] = $payload['metrics'];

            DB::connection($connection)
                ->table('observatory_requests')
                ->where('request_id', $payload['request_id'])
                ->update(['metrics_payload' => json_encode($existingMetrics)]);
        }

        return response()->json(['success' => true]);
    }
}
