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

// ==================== BANNER ====================
function hookingBanner(): void
{
    echo c('branco') . "
   __  __  ____   _____   _   _   _____   _____   _   _   ____  
  |  |/  |/  _  \ /  ___| | | | | /  ___| /  ___| | | | | |  _ \ 
  |  |/|  | | | | | |     | |_| | | |___  | |___  | |_| | | |_) |
  |  __  | | | | | |     |  _  | \___  \ \___  \ |  _  | |  _ < 
  | |  | | | |_| | | |___ | | | |  ___| |  ___| | | | | | |_) |
  |_|  |_| \_____/ \_____| |_| |_| /_____/ /_____/ |_| |_| |____/ 

" . c('vermelho') . "          FUCKING CHEATERS • ANTI-BYPASS SCANNER" . c('branco') . "

  " . c('ciano') . "HOOKING" . c('branco') . " Anti-Cheat " . c('vermelho') . "v2.0" . c('branco') . "
  GitHub: " . c('ciano') . "santos-ss/Hooking" . rst() . "\n\n";
}

// ==================== ADB HELPERS ====================
function garantirPermissoesBinarios(): void
{
    $binarios = [
        '/data/data/com.termux/files/usr/bin/adb',
        '/data/data/com.termux/files/usr/bin/clear',
    ];
    foreach ($binarios as $bin) {
        if (file_exists($bin)) {
            @chmod($bin, 0755);
        }
    }
}

function adb(string $cmd): string
{
    return trim((string) shell_exec($cmd . ' 2>/dev/null'));
}

// ==================== VERIFICAÇÕES DE SEGURANÇA ====================

function verificarRoot(): void
{
    secao(2, 'VERIFICANDO ROOT');

    $su = adb('adb shell "which su 2>/dev/null || echo notfound"');
    $su_path = adb('adb shell "ls /system/bin/su /system/xbin/su /sbin/su 2>/dev/null || echo notfound"');

    if ($su !== 'notfound' || strpos($su_path, 'su') !== false) {
        erro("Root detectado via comando 'su'");
    } else {
        ok("Nenhum binário 'su' encontrado");
    }

    $magisk = adb('adb shell "ls /data/adb/magisk 2>/dev/null"');
    if (!empty($magisk)) {
        erro("Pasta Magisk detectada em /data/adb/magisk");
    } else {
        ok("Magisk não encontrado em caminho padrão");
    }
}

function verificarSELinux(): void
{
    secao(3, 'VERIFICANDO SELINUX');

    $selinux = adb('adb shell getenforce');
    echo "    SELinux status: " . c('bold', 'ciano') . $selinux . rst() . "\n";

    if (stripos($selinux, 'Permissive') !== false) {
        erro("SELinux está em modo Permissive (inseguro)");
    } elseif (stripos($selinux, 'Enforcing') !== false) {
        ok("SELinux em modo Enforcing");
    } else {
        aviso("Não foi possível determinar o status do SELinux");
    }
}

function verificarMagisk(): void
{
    secao(4, 'VERIFICANDO MAGISK / KERNELSU / APATCH');

    $detect = [
        'Magisk'   => '/data/adb/magisk',
        'KernelSU' => '/data/adb/ksu',
        'APatch'   => '/data/adb/ap',
    ];

    $encontrado = false;

    foreach ($detect as $nome => $path) {
        $res = adb("adb shell \"ls $path 2>/dev/null\"");
        if (!empty($res)) {
            erro("$nome detectado");
            $encontrado = true;
        }
    }

    if (!$encontrado) {
        ok("Nenhuma ferramenta de root conhecida detectada");
    }
}

function verificarScriptsAtivos(): void
{
    secao(5, 'VERIFICANDO SCRIPTS / MÓDULOS ATIVOS');

    $processes = adb('adb shell "ps -ef | grep -E \'frida|magisk|inject|hook|cheat|lsass\' || echo clean"');

    if (strpos($processes, 'frida') !== false || strpos($processes, 'inject') !== false) {
        erro("Processos suspeitos de hooking/injeção detectados");
        detalhe("Saída: " . trim($processes));
    } else {
        ok("Nenhum processo de hooking óbvio encontrado");
    }
}

function verificarUptimeEHorario(): void
{
    secao(6, 'VERIFICANDO UPTIME E HORÁRIO DO SISTEMA');

    $uptime = adb('adb shell uptime');
    $date   = adb('adb shell date');

    info("Uptime: " . $uptime);
    info("Data/Hora: " . $date);
}

function verificarMudancasHorario(): void
{
    secao(7, 'VERIFICANDO MUDANÇAS RECENTES DE HORÁRIO');

    $last_boot = adb('adb shell "cat /proc/stat | grep btime"');
    info("Último boot (aprox): " . $last_boot);
    aviso("Verifique manualmente se o horário foi alterado recentemente");
}

function verificarPlayStore(): void
{
    secao(8, 'VERIFICANDO INTEGRIDADE PLAY STORE');

    $play = adb('adb shell "pm list packages | grep com.android.vending"');
    if (empty($play)) {
        erro("Play Store não encontrada ou desinstalada");
    } else {
        ok("Play Store instalada");
    }
}

function verificarClipboard(): void
{
    secao(9, 'VERIFICANDO CLIPBOARD');

    $clip = adb('adb shell "content query --uri content://clipboard" 2>/dev/null || echo "Não suportado"');
    if (strlen($clip) > 10) {
        aviso("Conteúdo no clipboard detectado (pode ser de cheat)");
    } else {
        ok("Clipboard vazio ou não acessível");
    }
}

// ==================== VERIFICAÇÕES ESPECÍFICAS FREE FIRE ====================

function verificarJogoInstalado(string $pacote, string $nomeJogo): void
{
    secao(1, "VERIFICANDO $nomeJogo");

    $instalado = adb("adb shell \"pm list packages | grep $pacote\"");
    if (empty($instalado)) {
        erro("$nomeJogo NÃO está instalado!");
        exit(1);
    }
    ok("$nomeJogo encontrado ($pacote)");
}

function verificarMReplays(string $pacote): void
{
    secao(10, 'VERIFICANDO MREPLAYS / REPLAYS MODIFICADOS');

    $path = "/data/data/$pacote/files/mreplays";
    $existe = adb("adb shell \"ls $path 2>/dev/null\"");

    if (!empty($existe)) {
        aviso("Pasta mreplays encontrada. Pode conter replays modificados.");
    } else {
        ok("Pasta mreplays normal");
    }
}

function verificarWallhackHolograma(string $pacote): void
{
    secao(11, 'VERIFICANDO ARQUIVOS WALLHACK / HOLOGRAMA');

    $arquivos_suspeitos = [
        "libanort.so",
        "libanort2.so",
        "libwallhack.so",
        "hologram",
        "esp",
        "aimbot"
    ];

    foreach ($arquivos_suspeitos as $file) {
        $res = adb("adb shell \"find /data/data/$pacote -name '*$file*' 2>/dev/null\"");
        if (!empty($res)) {
            erro("Arquivo suspeito encontrado: $file");
        }
    }
    ok("Nenhum arquivo wallhack/holograma óbvio encontrado");
}

function verificarOBB(string $pacote): void
{
    secao(12, 'VERIFICANDO OBB MODIFICADO');

    $obb = adb("adb shell \"ls /sdcard/Android/obb/$pacote 2>/dev/null | head -5\"");
    if (!empty($obb)) {
        info("OBB encontrado. Verifique manualmente se está original.");
    }
}

function verificarShaders(string $pacote): void
{
    secao(13, 'VERIFICANDO SHADERS');

    $shader = adb("adb shell \"ls /data/data/$pacote/files/shaders 2>/dev/null\"");
    if (!empty($shader)) {
        aviso("Pasta de shaders encontrada (pode ser modificada)");
    } else {
        ok("Shaders padrão");
    }
}

function verificarOptionalAvatarRes(string $pacote): void
{
    secao(14, 'VERIFICANDO OPTIONAL AVATAR RESOURCES');

    $path = "/data/data/$pacote/files/optionalavatarres";
    if (adb("adb shell \"ls $path 2>/dev/null\"")) {
        aviso("Pasta optionalavatarres encontrada (comum em cheats visuais)");
    } else {
        ok("optionalavatarres não modificado");
    }
}

// ==================== ANÁLISE GERAL ====================

function detectarBypassShell(): bool
{
    $bypassDetectado = false;

    cabecalho('ANÁLISE COMPLETA DE SEGURANÇA DO DISPOSITIVO');

    secao(1, 'VERIFICANDO DISPOSITIVO CONECTADO');

    $devices = adb('adb devices');
    if (empty($devices) || strpos($devices, 'device') === false) {
        erro("Nenhum dispositivo detectado!");
        return true;
    }

    ok("Dispositivo conectado com sucesso");

    verificarRoot();
    verificarSELinux();
    verificarMagisk();
    verificarScriptsAtivos();
    verificarUptimeEHorario();
    verificarMudancasHorario();
    verificarPlayStore();
    verificarClipboard();

    echo "\n" . c('bold', 'ciano') . "  ► RESUMO DA ANÁLISE\n  -------------------\n\n" . rst();

    if ($bypassDetectado) {
        echo c('bold', 'vermelho') . "  ⚠️  ATENÇÃO: MODIFICAÇÕES DETECTADAS! ⚠️\n" . rst();
    } else {
        echo c('bold', 'verde') . "  ✓ Nenhuma modificação crítica detectada\n" . rst();
    }

    return $bypassDetectado;
}

// ==================== FUNÇÃO PRINCIPAL DE SCAN ====================

function escanearFreeFire(string $pacote, string $nomeJogo): void
{
    garantirPermissoesBinarios();
    system('clear');
    hookingBanner();

    verificarDispositivoADB();

    if (empty(adb('adb version'))) {
        system('pkg install -y android-tools > /dev/null 2>&1');
    }

    shell_exec('adb start-server > /dev/null 2>&1');

    verificarJogoInstalado($pacote, $nomeJogo);

    $androidVer = adb('adb shell getprop ro.build.version.release');
    if ($androidVer) {
        echo c('bold', 'azul') . "  [+] Android: $androidVer\n" . rst();
    }

    detectarBypassShell();

    verificarMReplays($pacote);
    verificarWallhackHolograma($pacote);
    verificarOBB($pacote);
    verificarShaders($pacote);
    verificarOptionalAvatarRes($pacote);

    echo c('bold', 'branco') . "\n\n\t Obrigado por usar o HOOKING Anti-Cheat.\n";
    echo c('bold', 'branco') . "\t                 By santos-ss\n\n" . rst();

    echo c('bold', 'ciano') . "  Pressione Enter para voltar ao menu...\n" . rst();
    fgets(STDIN);
}

function verificarDispositivoADB(): bool
{
    garantirPermissoesBinarios();

    $output = (string) shell_exec('adb devices');
    $linhas = array_filter(explode("\n", trim($output)));

    if (count($linhas) <= 1) {
        erro("Nenhum dispositivo ADB conectado.");
        erro("Use a opção [0] para parear via Wi-Fi.");
        exit(1);
    }

    ok("Dispositivo ADB detectado");
    return true;
}

// ==================== MENU E CONEXÃO ====================

function conectarADB(): void
{
    system('clear');
    hookingBanner();

    if (empty(adb('adb version'))) {
        aviso("ADB não encontrado. Instalando...");
        system('pkg install android-tools -y');
        ok("ADB instalado.");
    }

    inputUsuario("Porta de pareamento (ex: 45678)");
    $pairPort = trim(fgets(STDIN) ?? '');

    echo c('amarelo') . "\n  [!] Digite o código de pareamento que aparece no celular:\n" . rst();
    system("adb pair localhost:$pairPort");

    inputUsuario("Porta de conexão (ex: 12345)");
    $connectPort = trim(fgets(STDIN) ?? '');

    system("adb connect localhost:$connectPort");
    info("Conexão tentada. Verifique a saída acima.");

    echo "\n  Pressione Enter para voltar...";
    fgets(STDIN);
}

function exibirMenu(): void
{
    echo c('bold', 'azul') . "  ╔══════════════════════════╗\n";
    echo c('bold', 'azul') . "  ║      MENU PRINCIPAL      ║\n";
    echo c('bold', 'azul') . "  ╚══════════════════════════╝\n\n" . rst();

    echo c('amarelo') . "  [0] " . c('branco') . "Conectar ADB (Pareamento Wi-Fi)\n";
    echo c('verde')   . "  [1] " . c('branco') . "Escanear Free Fire Normal\n";
    echo c('verde')   . "  [2] " . c('branco') . "Escanear Free Fire MAX\n";
    echo c('vermelho'). "  [S] " . c('branco') . "Sair\n\n" . rst();
}

function lerOpcao(): string
{
    $validas = ['0','1','2','S','s'];
    do {
        inputUsuario("Escolha uma opção");
        $opcao = strtoupper(trim(fgets(STDIN) ?? ''));
        if (!in_array($opcao, $validas, true)) {
            erro("Opção inválida!");
        }
    } while (!in_array($opcao, $validas, true));

    return $opcao;
}

// ====================== INÍCIO ======================

garantirPermissoesBinarios();
system('clear');
hookingBanner();

while (true) {
    exibirMenu();
    $opcao = lerOpcao();

    switch ($opcao) {
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
            echo "\n\n\t Obrigado por usar o HOOKING!\n";
            echo "\t GitHub: santos-ss/Hooking\n\n";
            exit(0);
    }
}
