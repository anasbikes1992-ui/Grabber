// Supabase Edge Function: health check
// deno deploy target

import { serve } from 'https://deno.land/std@0.168.0/http/server.ts'

serve(async (_req) => {
  return new Response(
    JSON.stringify({ status: 'ok', service: 'grabber-hub-lk', ts: new Date().toISOString() }),
    { headers: { 'Content-Type': 'application/json' } },
  )
})
