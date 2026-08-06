<?php

namespace App\Modules\Domain\Http\Controllers;

use App\Integrations\Contracts\DnsProvider;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DomainController extends Controller
{
    public function index(DnsProvider $dns)
    {
        return view('module-domain::index', [
            'dnsEnabled' => $dns->isEnabled(),
        ]);
    }

    public function check(Request $request, DnsProvider $dns)
    {
        $validated = $request->validate([
            'domain' => ['required', 'string', 'max:253', 'regex:/^([a-z0-9-]+\.)+[a-z]{2,}$/i'],
        ]);

        $domain = strtolower($validated['domain']);

        $available = ! checkdnsrr($domain, 'ANY');

        $records = $dns->isEnabled() ? $dns->listRecords($domain) : [];

        return back()->with('domain', $domain)->with('domain_available', $available)->with('records', $records);
    }

    public function createDnsRecord(Request $request, DnsProvider $dns)
    {
        $validated = $request->validate([
            'domain' => ['required', 'string'],
            'type' => ['required', 'string', 'in:A,AAAA,CNAME,MX,TXT,SRV'],
            'name' => ['required', 'string', 'max:253'],
            'content' => ['required', 'string', 'max:1024'],
            'ttl' => ['nullable', 'integer', 'min:60', 'max:86400'],
        ]);

        $dns->createRecord(
            $validated['domain'],
            $validated['type'],
            $validated['name'],
            $validated['content'],
            (int) ($validated['ttl'] ?? 3600),
        );

        return back()->with('success', 'DNS record created.');
    }
}
