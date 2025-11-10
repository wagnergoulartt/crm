<?php
/**
 * Sistema de Cobrança Bot WhatsApp - Login Simples
 */

require_once 'config.php';

// Redireciona se já estiver logado
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$erro = '';

// Processar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $senha = trim($_POST['senha'] ?? '');
    
    if (empty($usuario) || empty($senha)) {
        $erro = 'Preencha todos os campos';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, usuario FROM admin WHERE usuario = ? AND senha = ?");
            $stmt->execute([$usuario, $senha]);
            $admin = $stmt->fetch();
            
            if ($admin) {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_usuario'] = $admin['usuario'];
                header('Location: dashboard.php');
                exit;
            } else {
                $erro = 'Usuário ou senha incorretos';
            }
        } catch (Exception $e) {
            $erro = 'Erro no sistema';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Cobrança - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
        }
        
        .login-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h3 {
            color: #333;
            margin: 0;
        }
        
        .logo p {
            color: #666;
            margin: 5px 0 0 0;
            font-size: 14px;
        }
        
        .form-control {
            height: 50px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .btn-primary {
            height: 50px;
            border-radius: 8px;
            background: #667eea;
            border: none;
            width: 100%;
            font-weight: 600;
        }
        
        .btn-primary:hover {
            background: #5a6fd8;
        }
        
        .alert {
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="login-card">
        <div class="logo">
            <h3>Sistema de Cobrança</h3>
            <p>Bot WhatsApp</p>
        </div>
        
        <?php if ($erro): ?>
            <div class="alert alert-danger"><?php echo $erro; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="text" 
                   name="usuario" 
                   class="form-control" 
                   placeholder="Usuário" 
                   value="<?php echo htmlspecialchars($usuario ?? ''); ?>"
                   required>
                   
            <input type="password" 
                   name="senha" 
                   class="form-control" 
                   placeholder="Senha" 
                   required>
                   
            <button type="submit" class="btn btn-primary">
                Entrar
            </button>
        </form>
        
        <div class="text-center mt-3">
            <small class="text-muted">Sistema v1.0</small>
        </div>
    </div>

    <script>
        // Foco automático no primeiro campo
        document.querySelector('input[name="usuario"]').focus();
        
        // Enter para ir para próximo campo
        document.querySelector('input[name="usuario"]').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.querySelector('input[name="senha"]').focus();
            }
        });
        
        // Enter no campo senha submete o form
        document.querySelector('input[name="senha"]').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.querySelector('form').submit();
            }
        });
    </script>
</body>
</html>