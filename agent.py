import psutil
import requests
import time
import json
from datetime import datetime
import platform

# Configurações
URL_API = "http://localhost/monitor/api.php"
ID_SERVIDOR = platform.node()
def coletar_metricas():
    ram = psutil.virtual_memory().percent
    io_antes = psutil.disk_io_counters()
    cpu = psutil.cpu_percent(interval=1)
    io_depois = psutil.disk_io_counters()
    
    leitura = io_depois.read_bytes - io_antes.read_bytes
    escrita = io_depois.write_bytes - io_antes.write_bytes
    total_io = (leitura + escrita) / (1024 * 1024)
    total_io = round(total_io, 3)
    porcentagem_espaco = psutil.disk_usage('C:').percent
    
    payload = {
        "servidor": ID_SERVIDOR,
        "cpu": cpu,
        "ram": ram,
        "disco": total_io,
        "armazenamento": porcentagem_espaco,
        "timestamp": datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    }
    return payload

def enviar_dados(dados):
    try:
        response = requests.post(URL_API, data=dados)
        if response.status_code == 200:
            print(f"[OK] Dados enviados: CPU {dados['cpu']}% | RAM {dados['ram']}% | Disco {dados['disco']}MB/s | Armazenamento {dados['armazenamento']}%")
        else:
            print(f"[ERRO] Servidor respondeu com status: {response.status_code}")
    except Exception as e:
        print(f"[ERRO] Não foi possível conectar ao PHP: {e}")

if __name__ == "__main__":
    print(f"Iniciando monitoramento de {ID_SERVIDOR}...")
    while True:
        metricas = coletar_metricas()
        enviar_dados(metricas)
        time.sleep(1)