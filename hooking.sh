#!/bin/bash
# =====================================================
# Hooking Anti-Cheat - Free Fire (Termux)
# GitHub: santos-ss/Hooking
# Versão: 1.3 - TODAS logs de pareamento Bluetooth + ADB Wi-Fi + hookingSCAN.txt
# =====================================================

clear
echo -e "\e[31m[★] Iniciando Hooking Anti-Cheat - Modo Total Anti-Bypass\e[0m"

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

log "🚀 Iniciando scan completo - Log gerado: hooking_scan_$TIMESTAMP.log"

# ================== ANTI-BYPASS: CHECKSUM ==================
ORIGINAL_HASH= "eaa669cec3049644a44ce77a0ebdb4a5ed5d1a9c8124da9e4eb9a57515674657"

CURRENT_HASH=$(sha256sum "$0" 2>/dev/null | awk '{print $1}')

if [ "$CURRENT_HASH" != "$ORIGINAL_HASH" ]; then
    log "❌ ARQUIVO MODIFICADO! Bypass detectado. Saindo..."
    echo -e "\e[31m[!] Integridade violada!\e[0m"
    exit 1
fi
log "✅ Integridade verificada."

# ================== DEPENDÊNCIAS ==================
pkg install -y coreutils android-tools net-tools procps-ng busybox &>/dev/null

# ================== DETECÇÃO COMPLETA DE ROOT/KERNEL ==================
detect_root_magisk() {
    log "🔍 Escaneando QUALQUER ROOT ou KERNEL modificado..."
    su_paths=("/system/bin/su" "/system/xbin/su" "/sbin/su" "/data/bin/su" "/data/local/su" "/data/local/bin/su" "/data/adb/su" "/su/bin/su")
    for path in "${su_paths[@]}"; do
        [ -f "$path" ] && log "⚠️ ROOT DETECTADO: $path"
    done
    [ -d "/data/adb/magisk" ] || [ -d "/data/adb/zygisk" ] && log "⚠️ MAGISK/ZYGISK DETECTADO!"
    [ "$(getprop ro.secure 2>/dev/null)" = "0" ] && log "⚠️ ROOT FLAG DETECTADA (ro.secure=0)"
    command -v getenforce >/dev/null && getenforce | grep -q "Permissive" && log "⚠️ SELINUX PERMISSIVE"
}

# ================== TODAS AS LOGS DE PAREAMENTO (Bluetooth + ADB Wi-Fi) ==================
detect_pairing_logs() {
    log "🔍 Capturando TODAS as logs de pareamento/desparamento Bluetooth e ADB Wi-Fi..."

    echo "" >> "$SCAN_FILE"
    echo "=== TODAS LOGS DE PAREAMENTO/DESPAREAMENTO (Bluetooth + ADB Wi-Fi) ===" >> "$SCAN_FILE"
    echo "Data/Hora do scan: $(date +"%Y-%m-%d %H:%M:%S")" >> "$SCAN_FILE"
    echo "" >> "$SCAN_FILE"

    # TODAS as logs relevantes dos últimos 500 eventos
    logcat -b all -v time -t 500 2>/dev/null | grep -Ei "bluetooth|pairing|bond|unpair|BluetoothDevice|BluetoothAdapter|AdbDebuggingManager|wireless|adb pair|pair code|pairing code|connected|disconnected" >> "$SCAN_FILE" 2>/dev/null || echo "Nenhuma log de pareamento encontrada no momento." >> "$SCAN_FILE"

    log "✅ Todas logs de Bluetooth e ADB Wi-Fi salvas no hookingSCAN.txt"
}

# ================== ARQUIVOS SUSPEITOS + MREPLAYS + ADB HISTORY ==================
detect_adb_usb_history_and_suspicious() {
    echo "=== Hooking SCAN - $(date +"%Y-%m-%d %H:%M:%S") ===" > "$SCAN_FILE"
    echo "Relatório completo - santos-ss/Hooking" >> "$SCAN_FILE"
    echo "" >> "$SCAN_FILE"

    # Arquivos suspeitos
    echo "=== ARQUIVOS SUSPEITOS NA PASTA 0 ===" >> "$SCAN_FILE"
    find /storage/emulated/0 -maxdepth 5 -type f \( -name "*.lua" -o -name "*.so" -o -name "*mod*" -o -name "*hack*" -o -name "*cheat*" -o -name "*inject*" -o -name "*bypass*" -o -name "*gg*" -o -name "*frida*" \) 2>/dev/null | 
    while read -r file; do
        MOD_TIME=$(stat -c "%y" "$file" 2>/dev/null | cut -d. -f1)
        echo "🚨 $file | Mod: $MOD_TIME" >> "$SCAN_FILE"
    done

    # MReplays
    for ff in "com.dts.freefireth" "com.dts.freefiremax"; do
        path="/storage/emulated/0/Android/data/$ff/files/MReplays/"
        [ -d "$path" ] && {
            echo "" >> "$SCAN_FILE"
            echo "=== MReplays - $ff ===" >> "$SCAN_FILE"
            find "$path" -type f 2>/dev/null | while read -r file; do
                MOD_TIME=$(stat -c "%y" "$file" 2>/dev/null | cut -d. -f1)
                echo "Replay: $file | Mod: $MOD_TIME" >> "$SCAN_FILE"
            done
        }
    done

    detect_pairing_logs   # ← TODAS logs de Bluetooth + ADB Wi-Fi
}

# ================== LOOP PRINCIPAL ==================
log "🔄 Monitoramento contínuo iniciado..."

while true; do
    detect_root_magisk
    detect_adb_usb_history_and_suspicious

    log "⏳ Próximo scan em 8 segundos..."
    sleep 8
done
