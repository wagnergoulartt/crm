<?php
/**
 * Sistema de Cobrança Bot WhatsApp - Dashboard Principal
 */

require_once 'config.php';

// Verificar se está logado
requireLogin();

// Buscar dados para os cards
try {
    // Total de clientes
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM clientes");
    $total_clientes = $stmt->fetch()['total'];
    
    // Clientes ativos
    $stmt = $pdo->query("SELECT COUNT(*) as ativo FROM clientes WHERE status = 'ativo'");
    $clientes_ativos = $stmt->fetch()['ativo'];
    
    // Faturas vencidas
    $stmt = $pdo->query("SELECT COUNT(*) as vencidas FROM pagamentos WHERE status = 'pendente' AND data_vencimento < CURDATE()");
    $faturas_vencidas = $stmt->fetch()['vencidas'];
    
    // Receita total do mês atual
    $stmt = $pdo->query("SELECT SUM(valor_corrigido) as receita FROM pagamentos WHERE status = 'pago' AND MONTH(data_pagamento) = MONTH(NOW()) AND YEAR(data_pagamento) = YEAR(NOW())");
    $receita_mes = $stmt->fetch()['receita'] ?? 0;
    
} catch (Exception $e) {
    $total_clientes = 0;
    $clientes_ativos = 0;
    $faturas_vencidas = 0;
    $receita_mes = 0;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema de Cobrança</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', sans-serif;
        }
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-weight: 700;
            color: white !important;
        }
        
        .nav-link {
            color: white !important;
            font-weight: 500;
            margin: 0 10px;
            transition: all 0.3s;
        }
        
        .nav-link:hover {
            background: rgba(255,255,255,0.1);
            border-radius: 5px;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card-header {
            border-radius: 10px 10px 0 0 !important;
            font-weight: 600;
        }
        
        .stats-card {
            margin-bottom: 20px;
        }
        
        .stats-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        
        .content-area {
            min-height: 500px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 30px;
        }
        
        .btn-custom {
            border-radius: 8px;
            font-weight: 500;
            padding: 10px 20px;
        }
        
        .welcome-text {
            color: #666;
            margin-bottom: 30px;
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fab fa-whatsapp me-2"></i>Sistema de Cobrança
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#" onclick="loadPage('dashboard')">
                            <i class="fas fa-home me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" onclick="loadPage('clientes')">
                            <i class="fas fa-users me-1"></i>Clientes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" onclick="loadPage('produtos')">
                            <i class="fas fa-box me-1"></i>Produtos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" onclick="loadPage('pagamentos')">
                            <i class="fas fa-credit-card me-1"></i>Pagamentos
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo $_SESSION['admin_usuario']; ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt me-1"></i>Sair
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Container Principal -->
    <div class="container mt-4">
        <!-- Cards de Estatísticas -->
        <div class="row" id="stats-cards">
            <div class="col-md-3 stats-card">
                <div class="card bg-primary text-white">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0"><?php echo $total_clientes; ?></h3>
                            <small>Total Clientes</small>
                        </div>
                        <i class="fas fa-users stats-icon"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 stats-card">
                <div class="card bg-success text-white">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0"><?php echo $clientes_ativos; ?></h3>
                            <small>Clientes Ativos</small>
                        </div>
                        <i class="fas fa-user-check stats-icon"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 stats-card">
                <div class="card bg-danger text-white">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0"><?php echo $faturas_vencidas; ?></h3>
                            <small>Faturas Vencidas</small>
                        </div>
                        <i class="fas fa-exclamation-triangle stats-icon"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 stats-card">
                <div class="card bg-info text-white">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0"><?php echo formatMoney($receita_mes); ?></h3>
                            <small>Receita do Mês</small>
                        </div>
                        <i class="fas fa-dollar-sign stats-icon"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Área de Conteúdo Dinâmico -->
        <div class="row">
            <div class="col-12">
                <div class="content-area" id="content-area">
                    <div class="text-center">
                        <h2>Bem-vindo ao Sistema de Cobrança</h2>
                        <p class="welcome-text">
                            Olá <strong><?php echo $_SESSION['admin_usuario']; ?></strong>, 
                            selecione uma opção no menu acima para começar.
                        </p>
                        
                        <div class="row justify-content-center">
                            <div class="col-md-8">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <button class="btn btn-primary btn-custom w-100" onclick="loadPage('clientes')">
                                            <i class="fas fa-users d-block mb-2" style="font-size: 2rem;"></i>
                                            Gerenciar Clientes
                                        </button>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <button class="btn btn-success btn-custom w-100" onclick="loadPage('produtos')">
                                            <i class="fas fa-box d-block mb-2" style="font-size: 2rem;"></i>
                                            Gerenciar Produtos
                                        </button>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <button class="btn btn-info btn-custom w-100" onclick="loadPage('pagamentos')">
                                            <i class="fas fa-credit-card d-block mb-2" style="font-size: 2rem;"></i>
                                            Ver Pagamentos
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Navegação SPA simples
        function loadPage(page) {
            const contentArea = document.getElementById('content-area');
            
            // Mostrar loading
            contentArea.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                    <p class="mt-2">Carregando...</p>
                </div>
            `;
            
            // Atualizar menu ativo
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // Carregar página
            let url = '';
            switch(page) {
                case 'clientes':
                    url = 'cliente.php';
                    break;
                case 'produtos':
                    url = 'produto.php';
                    break;
                case 'pagamentos':
                    url = 'pagamentos.php';
                    break;
                default:
                    // Dashboard - recarregar página
                    location.reload();
                    return;
            }
            
            // Fazer requisição AJAX
            fetch(url)
                .then(response => response.text())
                .then(data => {
                    // Extrair apenas o conteúdo body da resposta
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(data, 'text/html');
                    const bodyContent = doc.body.innerHTML;
                    contentArea.innerHTML = bodyContent;
                    
                    // Executar scripts da página carregada
                    const scripts = contentArea.querySelectorAll('script');
                    scripts.forEach(script => {
                        const newScript = document.createElement('script');
                        newScript.textContent = script.textContent;
                        document.body.appendChild(newScript);
                    });
                })
                .catch(error => {
                    contentArea.innerHTML = `
                        <div class="alert alert-danger">
                            <h4>Erro ao carregar página</h4>
                            <p>Não foi possível carregar a página solicitada.</p>
                            <button class="btn btn-secondary" onclick="location.reload()">
                                Recarregar Dashboard
                            </button>
                        </div>
                    `;
                });
        }
        
        // Atualizar cards a cada 30 segundos
        setInterval(function() {
            fetch('dashboard.php')
                .then(response => response.text())
                .then(data => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(data, 'text/html');
                    const newStatsCards = doc.getElementById('stats-cards');
                    if (newStatsCards) {
                        document.getElementById('stats-cards').innerHTML = newStatsCards.innerHTML;
                    }
                })
                .catch(error => console.log('Erro ao atualizar stats'));
        }, 30000);
        
    </script>
</body>
</html>