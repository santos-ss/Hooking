#!/bin/bash
# =====================================================
# Hooking Anti-Cheat - Free Fire (Termux)
# GitHub: santos-ss/Hooking
# Versão: 2.0 - MUITO MAIOR | +10 novas funções + Shaders + Instalação
# =====================================================

clear
echo -e "\e[31m[★] Iniciando Hooking Anti-Cheat - Scanner COMPLETO\e[0m"
echo -e "\e[33m[⚠] Modo sem proteção de integridade (hash removido)\e[0m"

# ================== CONFIGURAÇÕES ==================
LOG_DIR="$HOME/hooking_logs"
mkdir -p "$LOG_DIR"
TIMESTAMP=$(date +"%Y-%m-%d_%H-%M-%S")
SCAN_LOG="$LOG_DIR/hooking_scan_$TIMESTAMP.log"
MAIN_LOG="$LOG_DIR/hooking_main.log"

SCAN_FILE="/storage/emulated/0/hookingSCAN.txt"

log() {
    echo -e "[$(date +"%Y-%m-%d %H:%M:%S")] [Hooking] $1" | tee -a "$MAIN_LOG" >> "$SCAN_LOG"
}

log "🚀 Iniciando scanner COMPLETO e expandido..."

# ================== DEPENDÊNCIAS ==================
log "📦 Instalando dependências necessárias..."
pkg install -y coreutils android-tools net-tools procps-ng busybox &>/dev/null

# ================== 1. DETECÇÃO DE ROOT E KERNEL ==================
detect_root_magisk() {
    log "🔍 [1/12] Escaneando ROOT, Magisk, Kernel modificado..."
    # (código mantido e expandido)
    su_paths=("/system/bin/su" "/system/xbin/su" "/sbin/su" "/data/bin/su" "/data/local/su" "/data/adb/su" "/su/bin/su")
    for path in "${su_paths[@]}"; do
        [ -f "$path" ] && log "⚠️ ROOT DETECTADO: $path"
    done
    [ -d "/data/adb/magisk" ] || [ -d "/data/adb/zygisk" ] && log "⚠️ MAGISK/ZYGISK DETECTADO!"
    [ "$(getprop ro.secure 2>/dev/null)" = "0" ] && log "⚠️ ROOT FLAG (ro.secure=0)"
}

# ================== 2. PASTA SHADERS (NOVA FUNÇÃO) ==================
detect_shaders() {
    log "🔍 [2/12] Escaneando pastas Shaders / ShaderCache..."
    echo "" >> "$SCAN_FILE"
    echo "=== PASTAS SHADERS / ShaderCache ===" >> "$SCAN_FILE"

    for game in "com.dts.freefireth" "com.dts.freefiremax"; do
        for folder in "shaders" "ShaderCache" "cache/shaders" "files/shaders"; do
            path="/storage/emulated/0/Android/data/$game/files/$folder/"
            if [ -d "$path" ]; then
                echo "✅ Shaders encontrado: $path" >> "$SCAN_FILE"
                find "$path" -type f 2>/dev/null | head -20 | while read -r file; do
                    MOD_TIME=$(stat -c "%y" "$file" 2>/dev/null | cut -d. -f1)
                    echo "   └─ $file | Mod: $MOD_TIME" >> "$SCAN_FILE"
                done
                log "⚠️ Pasta Shaders detectada no jogo $game"
            fi
        done
    done
}

# ================== 3. ONDE FOI INSTALADO O FREE FIRE (NOVA) ==================
detect_installation_info() {
    log "🔍 [3/12] Verificando onde o Free Fire foi instalado..."
    echo "" >> "$SCAN_FILE"
    echo "=== INFORMAÇÕES DE INSTALAÇÃO DO FREE FIRE ===" >> "$SCAN_FILE"

    for pkg in "com.dts.freefireth" "com.dts.freefiremax"; do
        if pm list packages | grep -q "$pkg"; then
            apk_path=$(pm path "$pkg" 2>/dev/null | cut -d: -f2)
            install_time=$(pm list packages -l "$pkg" 2>/dev/null | grep -o 'firstInstallTime=[^ ]*' | cut -d= -f2)
            version=$(dumpsys package "$pkg" 2>/dev/null | grep versionName | head -1 | awk '{print $2}')
            echo "✅ $pkg instalado em: $apk_path" >> "$SCAN_FILE"
            echo "   Versão: $version" >> "$SCAN_FILE"
            echo "   Instalado em: $install_time" >> "$SCAN_FILE"
            log "✅ Free Fire ($pkg) detectado"
        else
            echo "❌ $pkg NÃO encontrado" >> "$SCAN_FILE"
        fi
    done
}

# ================== 4. APLICATIVOS VIRTUAIS ==================
detect_virtual_apps() {
    log "🔍 [4/12] Escaneando aplicativos virtuais (VMs)..."
    suspicious_vms=("vmos" "f1vm" "parallel" "x8" "dual" "multi" "virtual" "nox" "bluestacks")
    for vm in "${suspicious_vms[@]}"; do
        if pm list packages | grep -qi "$vm"; then
            log "⚠️ APLICATIVO VIRTUAL DETECTADO: $vm"
        fi
    done
}

# ================== 5. PACOTES SUSPEITOS ==================
detect_suspicious_packages() {
    log "🔍 [5/12] Escaneando pacotes suspeitos instalados..."
    echo "" >> "$SCAN_FILE"
    echo "=== PACOTES SUSPEITOS INSTALADOS ===" >> "$SCAN_FILE"
    pm list packages 2>/dev/null | grep -Ei "gg|frida|cheat|hack|inject|bypass|gameguardian|shizuku" >> "$SCAN_FILE" || echo "Nenhum pacote suspeito encontrado." >> "$SCAN_FILE"
}

# ================== 6. OPÇÕES DE DESENVOLVEDOR E USB DEBUGGING ==================
detect_developer_options() {
    log "🔍 [6/12] Verificando Opções do Desenvolvedor e USB Debugging..."
    if settings get global development_settings_enabled 2>/dev/null | grep -q "1"; then
        log "⚠️ OPÇÕES DO DESENVOLVEDOR ATIVADAS"
    fi
    if settings get global adb_enabled 2>/dev/null | grep -q "1"; then
        log "⚠️ USB DEBUGGING ATIVADO"
    fi
    if settings get global adb_wifi_enabled 2>/dev/null | grep -q "1"; then
        log "⚠️ Wi-Fi Debugging ATIVADO"
    fi
}

# ================== OUTRAS FUNÇÕES (mantidas e expandidas) ==================
detect_pairing_logs() {
    log "🔍 [7/12] Capturando TODAS logs de pareamento Bluetooth + ADB Wi-Fi..."
    # (código anterior mantido)
    echo "" >> "$SCAN_FILE"
    echo "=== TODAS LOGS DE PAREAMENTO/DESPAREAMENTO ===" >> "$SCAN_FILE"
    logcat -b all -v time -t 800 2>/dev/null | grep -Ei "bluetooth|pairing|bond|unpair|BluetoothDevice|AdbDebuggingManager|pair code|connected|disconnected" >> "$SCAN_FILE" 2>/dev/null
}

detect_suspicious_files() {
    log "🔍 [8/12] Escaneando arquivos suspeitos na Pasta 0..."
    echo "" >> "$SCAN_FILE"
    echo "=== ARQUIVOS SUSPEITOS NA PASTA 0 ===" >> "$SCAN_FILE"
    find /storage/emulated/0 -maxdepth 6 -type f \( -name "*.lua" -o -name "*.so" -o -name "*mod*" -o -name "*hack*" -o -name "*cheat*" -o -name "*inject*" -o -name "*bypass*" -o -name "*gg*" -o -name "*frida*" -o -name "*shader*" \) 2>/dev/null | 
    while read -r file; do
        MOD_TIME=$(stat -c "%y" "$file" 2>/dev/null | cut -d. -f1)
        echo "🚨 $file | Mod: $MOD_TIME" >> "$SCAN_FILE"
    done
}

# MReplays (mantido)
detect_mreplays() {
    log "🔍 [9/12] Verificando MReplays..."
    # (código anterior)
    for game in "com.dts.freefireth" "com.dts.freefiremax"; do
        path="/storage/emulated/0/Android/data/$game/files/MReplays/"
        if [ -d "$path" ]; then
            echo "" >> "$SCAN_FILE"
            echo "=== MReplays - $game ===" >> "$SCAN_FILE"
            find "$path" -type f 2>/dev/null | while read -r file; do
                MOD_TIME=$(stat -c "%y" "$file" 2>/dev/null | cut -d. -f1)
                echo "Replay: $file | Mod: $MOD_TIME" >> "$SCAN_FILE"
            done
        fi
    done
}

# ================== FUNÇÃO PRINCIPAL QUE GERA O RELATÓRIO COMPLETO ==================
generate_full_report() {
    echo "=== Hooking SCAN COMPLETO - $(date +"%Y-%m-%d %H:%M:%S") ===" > "$SCAN_FILE"
    echo "Scanner expandido - santos-ss/Hooking" >> "$SCAN_FILE"
    echo "==================================================" >> "$SCAN_FILE"

    detect_root_magisk
    detect_installation_info
    detect_shaders
    detect_mreplays
    detect_suspicious_files
    detect_suspicious_packages
    detect_virtual_apps
    detect_developer_options
    detect_pairing_logs

    log "✅ Relatório completo salvo em /storage/emulated/0/hookingSCAN.txt"
}

# ================== LOOP PRINCIPAL ==================
log "🔄 Monitoramento contínuo iniciado (scanner expandido)..."

while true; do
    generate_full_report
    log "⏳ Próximo scan completo em 10 segundos..."
    sleep 10
done
