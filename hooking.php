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

function rst(): string
{
    return C['rst'];
}

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

// ==================== BANNER (VERSÃO SEGURA) ====================
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
  ╚═╝  ╚═╝ ╚═════╝  ╚═════╝ ╚═╝  ╚═╝╚═╝╚═╝  ╚═══╝ ╚═════╝ 

" . c('ciano') . "  Coded By: Hooking | Base: KellerSS | Termux Edition" . rst() . "\n\n";
}

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

function verificarDispositivoADB(): bool
{
    garantirPermissoesBinarios();

    $output  = (string) shell_exec('adb devices');
    $linhas  = array_slice(explode("\n", trim($output)), 1);
    $devices = [];

    foreach ($linhas as $linha) {
        $linha = trim($linha);
        if (!empty($linha) && strpos($linha, 'device') !== false) {
            $partes = preg_split('/\s+/', $linha);
            if (isset($partes[0])) {
                $devices[] = $partes[0];
            }
        }
    }

    $total = count($devices);

    if ($total === 0) {
        erro("Nenhum dispositivo encontrado.");
        erro("Faça o pareamento de IP ou conecte um dispositivo via USB.");
        exit(1);
    }

    if ($total > 1) {
        erro("Mais de um dispositivo/emulador conectado.");
        foreach ($devices as $dev) {
            echo "    - $dev\n";
        }
        exit(1);
    }

    ok("Dispositivo conectado com permissões adequadas");
    return true;
}

function verificarRoot(): void
{
    secao(2, 'VERIFICANDO ROOT');
    $su = adb('adb shell "which su 2>/dev/null || echo notfound"');
    if ($su !== 'notfound') {
        erro("Root detectado via comando 'su'");
    } else {
        ok("Nenhum binário 'su' encontrado");
    }
}

function verificarSELinux(): void
{
    secao(3, 'VERIFICANDO SELINUX');
    $selinux = adb('adb shell getenforce');
    echo "    SELinux status: " . c('bold', 'ciano') . $selinux . rst() . "\n";

    if (stripos($selinux, 'Permissive') !== false) {
        erro("SELinux está em modo Permissive (inseguro)");
    } else {
        ok("SELinux em modo Enforcing");
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
    } else {
        ok("Nenhum processo de hooking óbvio encontrado");
    }
}

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

function escanearFreeFire(string $pacote, string $nomeJogo): void
{
    garantirPermissoesBinarios();
    system('clear');
    hookingBanner();

    verificarDispositivoADB();

    if (empty(adb('adb version'))) {
        system('pkg install -y android-tools > /dev/null 2>&1');
    }

    date_default_timezone_set('America/Sao_Paulo');
    shell_exec('adb start-server > /dev/null 2>&1');

    verificarJogoInstalado($pacote, $nomeJogo);

    $androidVer = adb('adb shell getprop ro.build.version.release');
    if (!empty($androidVer)) {
        echo c('bold', 'azul') . "  [+] Versão do Android: $androidVer\n" . rst();
    }

    verificarRoot();
    verificarSELinux();
    verificarMagisk();
    verificarScriptsAtivos();

    echo "\n" . c('bold', 'ciano') . "  ► RESUMO DA ANÁLISE\n  -------------------\n\n" . rst();
    echo c('bold', 'verde') . "  ✓ VERIFICAÇÃO CONCLUÍDA ✓\n" . rst();

    echo c('bold', 'branco') . "\n\n\t Obrigado por usar o HOOKING Anti-Cheat.\n";
    echo c('bold', 'branco') . "\t                 By santos-ss\n\n" . rst();

    echo c('bold', 'ciano') . "  Pressione Enter para voltar ao menu...\n" . rst();
    fgets(STDIN);
}

function conectarADB(): void
{
    system('clear');
    hookingBanner();

    echo c('bold', 'azul') . "  → Verificando se o ADB está instalado...\n" . rst();
    if (empty(adb('adb version'))) {
        aviso("ADB não encontrado. Instalando android-tools...");
        system('pkg install android-tools -y');
        info("Android-tools instalado com sucesso!");
    } else {
        info("ADB já está instalado.");
    }

    echo "\n";
    inputUsuario("Qual a sua porta para o pareamento (ex: 45678)?");
    $pairPort = trim(fgets(STDIN, 1024) ?? '');

    if (!is_numeric($pairPort) || empty($pairPort)) {
        erro("Porta inválida! Retornando ao menu.");
        sleep(2);
        return;
    }

    echo c('bold', 'amarelo') . "\n  [!] Agora, digite o código de pareamento que aparece no celular e pressione Enter.\n" . rst();
    system('adb pair localhost:' . intval($pairPort));

    echo "\n";
    inputUsuario("Qual a sua porta para a conexão (ex: 12345)?");
    $connectPort = trim(fgets(STDIN, 1024) ?? '');

    if (!is_numeric($connectPort) || empty($connectPort)) {
        erro("Porta inválida! Retornando ao menu.");
        sleep(2);
        return;
    }

    echo c('bold', 'amarelo') . "\n  [!] Conectando ao dispositivo...\n" . rst();
    system('adb connect localhost:' . intval($connectPort));
    info("Processo de conexão finalizado. Verifique a saída acima.");

    echo c('bold', 'branco') . "\n  [+] Pressione Enter para voltar ao menu...\n" . rst();
    fgets(STDIN, 1024);
}

function exibirMenu(): void
{
    echo c('bold', 'azul') . "  ╔══════════════════════════╗\n";
    echo c('bold', 'azul') . "  ║      MENU PRINCIPAL      ║\n";
    echo c('bold', 'azul') . "  ╚══════════════════════════╝\n\n" . rst();

    echo c('amarelo') . "  [0] " . c('branco') . "Conectar ADB " . c('cinza') . "(Pareamento e conexão via ADB)\n" . rst();
    echo c('verde')   . "  [1] " . c('branco') . "Escanear FreeFire Normal\n" . rst();
    echo c('verde')   . "  [2] " . c('branco') . "Escanear FreeFire Max\n" . rst();
    echo c('vermelho'). "  [S] " . c('branco') . "Sair\n\n" . rst();
}

function lerOpcao(): string
{
    $validas = ['0', '1', '2', 'S', 's'];
    do {
        inputUsuario("Escolha uma das opções acima");
        $opcao = trim(fgets(STDIN, 1024) ?? '');
        if (!in_array($opcao, $validas, true)) {
            erro("Opção inválida! Tente novamente.");
            echo "\n";
        }
    } while (!in_array($opcao, $validas, true));

    return strtoupper($opcao);
}

// ====================== INÍCIO DO SCRIPT ======================

garantirPermissoesBinarios();
system('clear');
hookingBanner();
sleep(1);
echo "\n";

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
            escanearFreeFire('com.dts.freefireth', 'FreeFire Normal');
            break;

        case '2':
            escanearFreeFire('com.dts.freefiremax', 'Free Fire MAX');
            break;

        case 'S':
            echo "\n\n\t Obrigado por usar o HOOKING Anti-Cheat.\n";
            echo "\t GitHub: santos-ss/Hooking\n\n";
            exit(0);
    }
}
