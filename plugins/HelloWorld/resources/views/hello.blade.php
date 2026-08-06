<x-client-layout title="Hello plugin">
    <div class="mx-auto max-w-2xl space-y-4">
        <div class="card">
            <h1 class="text-lg font-semibold text-white">Hello from the HelloWorld plugin 👋</h1>
            <p class="mt-2 text-sm text-slate-400">
                This page is rendered from <code>plugins/HelloWorld/resources/views/hello.blade.php</code>.
                If you can see it, the plugin system is working.
            </p>
            <div class="mt-4 rounded-lg border border-slate-800 bg-slate-900/60 p-4 text-xs text-slate-400">
                <p>Plugin manifest: <span class="text-violet-300">plugins/HelloWorld/plugin.json</span></p>
                <p class="mt-1">Service provider: <span class="text-violet-300">Plugins\HelloWorld\HelloWorldServiceProvider</span></p>
                <p class="mt-1">Namespace: <span class="text-violet-300">Plugins\HelloWorld</span> → mapped to <span class="text-violet-300">plugins/</span> in composer.json</p>
            </div>
        </div>
    </div>
</x-client-layout>
