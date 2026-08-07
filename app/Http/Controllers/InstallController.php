<?php

namespace App\Http\Controllers;

use App\Services\InstallationService;
use Illuminate\Http\Request;
use RuntimeException;

class InstallController extends Controller
{
    public function __construct(protected InstallationService $installer) {}

    public function index(Request $request)
    {
        $step = max(1, min(6, (int) $request->session()->get('install.step', 1)));

        return view('install.index', [
            'step' => $step,
            'requirements' => $this->installer->serverRequirements(),
            'data' => $request->session()->get('install.data', []),
        ]);
    }

    public function requirements(Request $request)
    {
        if (! $this->installer->requirementsPass()) {
            return back()->withErrors(['requirements' => 'Resolve all server requirements before continuing.']);
        }

        $request->session()->put('install.step', 2);

        return redirect()->route('install.index');
    }

    public function database(Request $request)
    {
        abort_unless((int) $request->session()->get('install.step', 1) === 2, 419);

        $data = $request->validate([
            'db_connection' => ['required', 'in:mysql,mariadb'],
            'db_host' => ['required', 'string', 'max:255'],
            'db_port' => ['required', 'integer', 'between:1,65535'],
            'db_database' => ['required', 'string', 'max:255'],
            'db_username' => ['required', 'string', 'max:255'],
            'db_password' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->installer->configureEnvironment([
                'DB_CONNECTION' => $data['db_connection'],
                'DB_HOST' => $data['db_host'],
                'DB_PORT' => $data['db_port'],
                'DB_DATABASE' => $data['db_database'],
                'DB_USERNAME' => $data['db_username'],
                'DB_PASSWORD' => $data['db_password'] ?? '',
            ]);
            $this->installer->testDatabaseConnection();
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['database' => $exception->getMessage()]);
        }

        $request->session()->put('install.data', array_merge($request->session()->get('install.data', []), $data));
        $request->session()->put('install.step', 3);

        return redirect()->route('install.index');
    }

    public function key(Request $request)
    {
        abort_unless((int) $request->session()->get('install.step', 1) === 3, 419);

        try {
            $this->installer->generateKey();
        } catch (RuntimeException $exception) {
            return back()->withErrors(['key' => $exception->getMessage()]);
        }

        $request->session()->put('install.step', 4);

        return redirect()->route('install.index');
    }

    public function migrate(Request $request)
    {
        abort_unless((int) $request->session()->get('install.step', 1) === 4, 419);

        try {
            $this->installer->testDatabaseConnection();
            $this->installer->migrate();
        } catch (RuntimeException $exception) {
            return back()->withErrors(['migration' => $exception->getMessage()]);
        }

        $request->session()->put('install.step', 5);

        return redirect()->route('install.index');
    }

    public function finish(Request $request)
    {
        abort_unless((int) $request->session()->get('install.step', 1) === 5, 419);

        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:255'],
            'site_url' => ['required', 'url', 'max:255'],
            'currency' => ['required', 'string', 'size:3'],
            'locale' => ['required', 'string', 'max:10'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255'],
            'admin_password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        try {
            $this->installer->configureEnvironment([
                'APP_NAME' => $data['site_name'],
                'APP_URL' => $data['site_url'],
                'APP_LOCALE' => $data['locale'],
                'KELVCMC_BRAND_NAME' => $data['site_name'],
                'KELVCMC_CURRENCY' => strtoupper($data['currency']),
            ]);
            $this->installer->seedBase();
            $this->installer->seedDemo();
            $this->installer->createAdmin($data['admin_name'], $data['admin_email'], $data['admin_password']);
            $this->installer->saveSettings($data['site_name'], $data['site_url'], strtoupper($data['currency']), $data['locale']);
            $this->installer->createStorageLink();
            $this->installer->lock();
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['installation' => $exception->getMessage()]);
        }

        $request->session()->forget('install');

        // Render in this request because the installer switches the default
        // session/cache drivers back to their production values before the
        // next request.
        return response()->view('install.complete');
    }

    public function complete()
    {
        abort_unless($this->installer->isInstalled(), 404);

        return view('install.complete');
    }
}
