<?php

declare(strict_types=1);

const C = [
    'rst'      => "\e[0m",
    'bold'     => "\e[1m",
    'branco'   => "\e[97m",
    'cinza'    => "\e[37m",
    'preto'    => "\e[30m\e[1m",
    'vermelho' => "\e[91m",
    'verde'    => "\e[92m",
    'fverde'   => "\e[32m",
    'amarelo'  => "\e[93m",
    'laranja'  => "\e[38;5;208m",
    'azul'     => "\e[34m",
    'ciano'    => "\e[36m",
    'magenta'  => "\e[35m",
];

function c(string ...$nomes): string
{
    return implode('', array_map(fn($n) => C[$n] ?? '', $nomes));
}

function rst(): string { return C['rst']; }

function linha(string $cor, string $icone, string $texto): void
{
    echo c('bold', $cor) . "  $icone $texto\n" . rst();
}

function ok(string $texto): void     { linha('verde',    '✓', $texto); }
function erro(string $texto): void   { linha('vermelho', '✗', $texto); }
function aviso(string $texto): void  { linha('amarelo',  '⚠', $texto); }
function info(string $texto): void   { linha('fverde',   'ℹ', $texto); }
function detalhe(string $texto): void
{
    echo c('bold', 'amarelo') . "    $texto\n" . rst();
}

function secao(int $num, string $titulo): void
{
    $sep = str_repeat('─', mb_strlen($titulo) + 4);
    echo "\n" . c('bold', 'azul') . "  ► [$num] $titulo\n  $sep\n" . rst();
}

function cabecalho(string $titulo): void
{
    echo "\n" . c('bold', 'ciano') . "  $titulo\n  " . str_repeat('=', mb_strlen($titulo)) . "\n\n" . rst();
}

function inputUsuario(string $mensagem): void
{
    echo c('rst', 'bold', 'ciano') . "  ▸ $mensagem: " . c('fverde');
}

// ==================== BANNER CORRIGIDO ====================
function hookingBanner(): void
{
    system('clear');

    echo c('branco') . "
   __  __  ____   _____   _   _   _____   _____   _   _   ____  
  |  |/  |/  _  \ /  ___| | | | | /  ___| /  ___| | | | | |  _ \ 
  |  |/|  | | | | | |     | |_| | | |___  | |___  | |_| | | |_) |
  |  __  | | | | | |     |  _  | \___  \ \___  \ |  _  | |  _ < 
  | |  | | | |_| | | |___ | | | |  ___| |  ___| | | | | | |_) |
  |_|  |_| \_____/ \_____| |_| |_| /_____/ /_____/ |_| |_| |____/ 

" . c('vermelho') . "          FUCKING CHEATERS • ANTI-BYPASS SCANNER" . c('branco') . "

  " . c('ciano') . "HOOKING" . c('branco') . " Anti-Cheat " . c('vermelho') . "v2.0" . c('branco') . "
  GitHub: " . c('ciano') . "santos-ss/Hooking" . c('branco') . " • Termux Edition

" . c('magenta') . "  ██████╗  ██████╗  ██████╗ ██╗  ██╗██╗███╗   ██╗ ██████╗ 
  ██╔══██╗██╔═══██╗██╔════╝ ██║ ██╔╝██║████╗  ██║██╔════╝ 
  ██████╔╝██║   ██║██║  ███╗█████╔╝ ██║██╔██╗ ██║██║  ███╗
  ██╔══██╗██║   ██║██║   ██║██╔═██╗ ██║██║╚██╗██║██║   ██║
  ██║  ██║╚██████╔╝╚██████╔╝██║  ██╗██║██║ ╚████║╚██████╔╝
  ╚═╝  ╚╝ ╚═════╝  ╚═════╝ ╚═╝  ╚═╝╚═╝╚═╝  ╚═══╝ ╚═════╝ 

" . c('ciano') . "  Coded By: Hooking | Base: KellerSS | Termux Edition" . rst() . "\n\n";
}

// ==================== ADB HELPERS ====================
function garantirPermissoesBinarios(): void
{
    $binarios = [
        '/data/data/com.termux/files/usr/bin/adb',
        '/data/data/com.termux/files/usr/bin/clear',
    ];
    foreach ($binarios as $bin) {
        if (file_exists($bin)) @chmod($bin, 0755);
    }
}

function adb(string $cmd): string
{
    return trim((string) shell_exec($cmd . ' 2>/dev/null'));
}

// ==================== VERIFICAÇÕES ====================
function verificarDispositivoADB(): bool
{
    garantirPermissoesBinarios();
    $output = (string) shell_exec('adb devices');
    if (strpos($output, 'device') === false) {
        erro("Nenhum dispositivo ADB conectado!");
        erro("Use a opção [0] → Conectar ADB");
        exit(1);
    }
    ok("Dispositivo ADB detectado");
    return true;
}

function verificarRoot(): void
{
    secao(2, 'VERIFICANDO ROOT');
    $su = adb('adb shell "which su 2>/dev/null || echo notfound"');
    if ($su !== 'notfound') {
        erro("Root detectado (su encontrado)");
    } else {
        ok("Nenhum binário su encontrado");
    }
}

function verificarSELinux(): void
{
    secao(3, 'VERIFICANDO SELINUX');
    $status = adb('adb shell getenforce');
    echo "    Status: " . c('bold', 'ciano') . $status . rst() . "\n";
    if (stripos($status, 'Permissive') !== false) {
        erro("SELinux em modo Permissive (inseguro)");
    } else {
        ok("SELinux em modo Enforcing");
    }
}

function verificarMagisk(): void
{
    secao(4, 'VERIFICANDO MAGISK / KERNELSU / APATCH');
    $detect = ['Magisk' => '/data/adb/magisk', 'KernelSU' => '/data/adb/ksu', 'APatch' => '/data/adb/ap'];
    $encontrado = false;
    foreach ($detect as $nome => $path) {
        if (adb("adb shell \"ls $path 2>/dev/null\"")) {
            erro("$nome detectado");
            $encontrado = true;
        }
    }
    if (!$encontrado) ok("Nenhuma ferramenta de root detectada");
}

function verificarScriptsAtivos(): void
{
    secao(5, 'VERIFICANDO PROCESSOS DE HOOK');
    $proc = adb('adb shell "ps -ef | grep -E \'frida|magisk|inject|hook|cheat|lsass\' || echo clean"');
    if (strpos($proc, 'frida') !== false || strpos($proc, 'inject') !== false) {
        erro("Processos de hooking detectados!");
    } else {
        ok("Nenhum processo suspeito encontrado");
    }
}

function verificarJogoInstalado(string $pacote, string $nome): void
{
    secao(1, "VERIFICANDO $nome");
    if (adb("adb shell \"pm list packages | grep $pacote\"")) {
        ok("$nome encontrado");
    } else {
        erro("$nome NÃO está instalado!");
        exit(1);
    }
}

// ==================== FUNÇÃO PRINCIPAL ====================
function escanearFreeFire(string $pacote, string $nomeJogo): void
{
    system('clear');
    hookingBanner();

    verificarDispositivoADB();

    if (empty(adb('adb version'))) {
        system('pkg install -y android-tools > /dev/null 2>&1');
    }

    shell_exec('adb start-server > /dev/null 2>&1');

    verificarJogoInstalado($pacote, $nomeJogo);

    $android = adb('adb shell getprop ro.build.version.release');
    if ($android) echo c('bold', 'azul') . "  [+] Android: $android\n" . rst();

    verificarRoot();
    verificarSELinux();
    verificarMagisk();
    verificarScriptsAtivos();

    echo "\n" . c('bold', 'verde') . "  ✓ ANÁLISE CONCLUÍDA ✓\n" . rst();
    echo c('branco') . "\n  Obrigado por usar o HOOKING Anti-Cheat\n";
    echo c('branco') . "  By santos-ss\n\n" . rst();

    echo c('ciano') . "  Pressione Enter para voltar ao menu...\n" . rst();
    fgets(STDIN);
}

// ==================== MENU ====================
function conectarADB(): void
{
    system('clear');
    hookingBanner();

    if (empty(adb('adb version'))) {
        aviso("Instalando ADB...");
        system('pkg install android-tools -y');
    }

    inputUsuario("Porta de pareamento (ex: 45678)");
    $port = trim(fgets(STDIN) ?? '');
    system("adb pair localhost:$port");

    inputUsuario("Porta de conexão (ex: 12345)");
    $port = trim(fgets(STDIN) ?? '');
    system("adb connect localhost:$port");

    echo "\n  Pressione Enter para voltar...";
    fgets(STDIN);
}

function exibirMenu(): void
{
    echo c('bold', 'azul') . "  ╔══════════════════════════╗\n";
    echo c('bold', 'azul') . "  ║      MENU PRINCIPAL      ║\n";
    echo c('bold', 'azul') . "  ╚══════════════════════════╝\n\n" . rst();

    echo c('amarelo') . "  [0] Conectar ADB (Wi-Fi)\n";
    echo c('verde')   . "  [1] Escanear Free Fire Normal\n";
    echo c('verde')   . "  [2] Escanear Free Fire MAX\n";
    echo c('vermelho'). "  [S] Sair\n\n" . rst();
}

function lerOpcao(): string
{
    do {
        inputUsuario("Escolha uma opção");
        $op = strtoupper(trim(fgets(STDIN) ?? ''));
    } while (!in_array($op, ['0','1','2','S'], true));

    return $op;
}

// ====================== INÍCIO ======================
garantirPermissoesBinarios();
system('clear');
hookingBanner();

while (true) {
    exibirMenu();
    $op = lerOpcao();

    switch ($op) {
        case '0':
            conectarADB();
            system('clear');
            hookingBanner();
            break;
        case '1':
            escanearFreeFire('com.dts.freefireth', 'Free Fire Normal');
            break;
        case '2':
            escanearFreeFire('com.dts.freefiremax', 'Free Fire MAX');
            break;
        case 'S':
            echo "\n\n  Obrigado por usar o HOOKING!\n\n";
            exit(0);
    }
}
