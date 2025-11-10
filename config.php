<?php
/**
 * Configurações Globais do Sistema de Cobrança para Bot WhatsApp
 * 
 * Este arquivo contém todas as configurações centralizadas do sistema:
 * - Conexão com banco de dados
 * - Tokens de integração
 * - Configurações de negócio
 * - URLs e caminhos
 */

// ========================================
// CONFIGURAÇÕES DE TIMEZONE
// ========================================
date_default_timezone_set('America/Sao_Paulo');

// ========================================
// CONFIGURAÇÕES DO BANCO DE DADOS
// ========================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'u529068110_crm');
define('DB_USER', 'u529068110_crm');
define('DB_PASS', '@Erick91492832');
define('DB_CHARSET', 'utf8mb4');

// ========================================
// CONFIGURAÇÕES DO MERCADO PAGO
// ========================================
// TOKEN DE ACESSO DO MERCADO PAGO (SUBSTITUA PELO SEU TOKEN REAL)
define('MP_ACCESS_TOKEN', 'APP_USR-SEU_TOKEN_AQUI'); // ⚠️ ALTERE ESTE TOKEN!

// URLs para retorno e notificação do Mercado Pago
define('BASE_URL', 'https://seudominio.com.br/sistema_cobranca/'); // ⚠️ ALTERE ESTA URL!
define('MP_SUCCESS_URL', BASE_URL . 'pagamentos.php?status=sucesso');
define('MP_PENDING_URL', BASE_URL . 'pagamentos.php?status=pendente');
define('MP_FAILURE_URL', BASE_URL . 'pagamentos.php?status=falha');
define('MP_WEBHOOK_URL', BASE_URL . 'webhook_mercadopago.php');

// ========================================
// CONFIGURAÇÕES DE COBRANÇA E MULTA
// ========================================
// Multa padrão aplicada por dia de atraso (%)
define('MULTA_PADRAO_PERCENTUAL', 2.00);

// Dias de carência antes de começar os lembretes (3 dias após vencimento)
define('DIAS_CARENCIA', 3);

// Valor mínimo para cobrança
define('VALOR_MINIMO_COBRANCA', 1.00);

// ========================================
// TOKEN DE SEGURANÇA INTERNO
// ========================================
// Token para comunicação segura entre o bot e o sistema
define('BOT_SECURITY_TOKEN', 'crm_bot_2024_' . md5('sistema_cobranca_whatsapp')); // Token gerado automaticamente

// ========================================
// CONFIGURAÇÕES DE SESSÃO
// ========================================
// Configurações para sessão PHP
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Mude para 1 se usar HTTPS
session_start();

// ========================================
// CONEXÃO COM BANCO DE DADOS
// ========================================
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die("Erro de conexão com o banco de dados: " . $e->getMessage());
}

// ========================================
// FUNÇÕES UTILITÁRIAS GLOBAIS
// ========================================

/**
 * Função para verificar se o usuário está logado
 */
function isLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

/**
 * Função para redirecionar usuário não logado
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Função para formatar valores monetários
 */
function formatMoney($value) {
    return 'R$ ' . number_format($value, 2, ',', '.');
}

/**
 * Função para formatar datas brasileiras
 */
function formatDateBR($date) {
    return date('d/m/Y', strtotime($date));
}

/**
 * Função para formatar data e hora brasileiras
 */
function formatDateTimeBR($datetime) {
    return date('d/m/Y H:i:s', strtotime($datetime));
}

/**
 * Função para validar token de segurança
 */
function validateSecurityToken($token) {
    return $token === BOT_SECURITY_TOKEN;
}

/**
 * Função para calcular multa por dias de atraso
 */
function calcularMulta($valor_original, $data_vencimento, $percentual_multa = null) {
    $percentual = $percentual_multa ?? MULTA_PADRAO_PERCENTUAL;
    
    $hoje = new DateTime();
    $vencimento = new DateTime($data_vencimento);
    
    if ($hoje <= $vencimento) {
        return $valor_original; // Não há multa se não venceu
    }
    
    $dias_atraso = $hoje->diff($vencimento)->days;
    $multa = ($valor_original * $percentual / 100) * $dias_atraso;
    
    return $valor_original + $multa;
}

/**
 * Função para gerar logs do sistema
 */
function logSistema($mensagem, $nivel = 'INFO') {
    $log_file = __DIR__ . '/logs/sistema.log';
    $log_dir = dirname($log_file);
    
    // Criar diretório de logs se não existir
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
    $usuario = $_SESSION['admin_usuario'] ?? 'SISTEMA';
    
    $log_entry = "[{$timestamp}] [{$nivel}] [{$ip}] [{$usuario}] {$mensagem}" . PHP_EOL;
    
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

/**
 * Função para sanitizar entrada de dados
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Função para validar formato de WhatsApp
 */
function validateWhatsApp($whatsapp) {
    // Remove todos os caracteres não numéricos
    $clean = preg_replace('/\D/', '', $whatsapp);
    
    // Verifica se tem entre 10 e 15 dígitos
    return strlen($clean) >= 10 && strlen($clean) <= 15;
}

/**
 * Função para enviar resposta JSON
 */
function jsonResponse($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ========================================
// CONSTANTES DO SISTEMA
// ========================================
define('SISTEMA_NOME', 'Sistema de Cobrança WhatsApp Bot');
define('SISTEMA_VERSAO', '1.0.0');
define('SISTEMA_AUTOR', 'Sistema CRM Bot');

// Status disponíveis para clientes
define('STATUS_CLIENTE_ATIVO', 'ativo');
define('STATUS_CLIENTE_VENCIDO', 'vencido');
define('STATUS_CLIENTE_SUSPENSO', 'suspenso');

// Status disponíveis para pagamentos
define('STATUS_PAGAMENTO_PENDENTE', 'pendente');
define('STATUS_PAGAMENTO_PAGO', 'pago');
define('STATUS_PAGAMENTO_CANCELADO', 'cancelado');

// Tipos de lembretes
define('LEMBRETE_PRIMEIRO', 'primeiro_aviso');
define('LEMBRETE_SEGUNDO', 'segundo_aviso');
define('LEMBRETE_ULTIMO', 'ultimo_aviso');
define('LEMBRETE_SAIR_GRUPO', 'sair_grupo');

// ========================================
// CONFIGURAÇÕES DE DESENVOLVIMENTO
// ========================================
// Ativar apenas em desenvolvimento
if ($_SERVER['SERVER_NAME'] === 'localhost' || strpos($_SERVER['SERVER_NAME'], '127.0.0.1') !== false) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    define('DEBUG_MODE', true);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    define('DEBUG_MODE', false);
}

// ========================================
// INICIALIZAÇÃO FINAL
// ========================================
// Log de inicialização do sistema
if (DEBUG_MODE) {
    logSistema("Sistema inicializado - Versão: " . SISTEMA_VERSAO);
}

// Verificar se todas as tabelas existem (apenas em modo debug)
if (DEBUG_MODE) {
    try {
        $tabelas_necessarias = ['admin', 'produtos', 'clientes', 'pagamentos', 'lembretes'];
        $stmt = $pdo->query("SHOW TABLES");
        $tabelas_existentes = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($tabelas_necessarias as $tabela) {
            if (!in_array($tabela, $tabelas_existentes)) {
                logSistema("AVISO: Tabela '{$tabela}' não encontrada no banco de dados!", 'WARNING');
            }
        }
    } catch (Exception $e) {
        logSistema("Erro ao verificar tabelas: " . $e->getMessage(), 'ERROR');
    }
}

?>