<?php
/**
 * Sistema de Cobrança Bot WhatsApp - Gerenciamento de Clientes
 * CRUD completo de clientes - VERSÃO CORRIGIDA
 */

require_once 'config.php';

// Verificar se está logado
requireLogin();

// Variáveis para mensagens
$mensagem = '';
$tipo_mensagem = '';

// Processar ações do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    try {
        switch ($acao) {
            case 'adicionar':
                $nome = trim($_POST['nome']);
                $whatsapp = trim($_POST['whatsapp']);
                $grupo_id = trim($_POST['grupo_id']);
                $produto_id = (int)$_POST['produto_id'];
                
                if (empty($nome) || empty($whatsapp) || empty($grupo_id) || !$produto_id) {
                    throw new Exception('Preencha todos os campos obrigatórios');
                }
                
                // Verificar se grupo_id já existe
                $stmt = $pdo->prepare("SELECT id FROM clientes WHERE grupo_id = ?");
                $stmt->execute([$grupo_id]);
                if ($stmt->fetch()) {
                    throw new Exception('Este Grupo ID já está cadastrado');
                }
                
                // Inserir cliente
                $proximo_vencimento = date('Y-m-d', strtotime('+30 days'));
                $stmt = $pdo->prepare("INSERT INTO clientes (nome, whatsapp, grupo_id, produto_id, proximo_vencimento) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$nome, $whatsapp, $grupo_id, $produto_id, $proximo_vencimento]);
                
                $cliente_id = $pdo->lastInsertId();
                
                // Gerar cobrança automática
                $stmt = $pdo->prepare("SELECT valor FROM produtos WHERE id = ?");
                $stmt->execute([$produto_id]);
                $produto = $stmt->fetch();
                
                if ($produto) {
                    $stmt = $pdo->prepare("INSERT INTO pagamentos (cliente_id, valor_original, valor_corrigido, data_vencimento) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$cliente_id, $produto['valor'], $produto['valor'], $proximo_vencimento]);
                }
                
                $mensagem = 'Cliente adicionado com sucesso!';
                $tipo_mensagem = 'success';
                break;
                
            case 'editar':
                $id = (int)$_POST['id'];
                $nome = trim($_POST['nome']);
                $whatsapp = trim($_POST['whatsapp']);
                $grupo_id = trim($_POST['grupo_id']);
                $produto_id = (int)$_POST['produto_id'];
                $status = $_POST['status'];
                
                if (empty($nome) || empty($whatsapp) || empty($grupo_id) || !$produto_id) {
                    throw new Exception('Preencha todos os campos obrigatórios');
                }
                
                $stmt = $pdo->prepare("UPDATE clientes SET nome = ?, whatsapp = ?, grupo_id = ?, produto_id = ?, status = ? WHERE id = ?");
                $stmt->execute([$nome, $whatsapp, $grupo_id, $produto_id, $status, $id]);
                
                $mensagem = 'Cliente atualizado com sucesso!';
                $tipo_mensagem = 'success';
                break;
                
            case 'excluir':
                $id = (int)$_POST['id'];
                $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = ?");
                $stmt->execute([$id]);
                
                $mensagem = 'Cliente excluído com sucesso!';
                $tipo_mensagem = 'success';
                break;
        }
    } catch (Exception $e) {
        $mensagem = $e->getMessage();
        $tipo_mensagem = 'danger';
    }
}

// Buscar clientes com produtos
$stmt = $pdo->query("
    SELECT c.*, p.nome as produto_nome, p.valor as produto_valor 
    FROM clientes c 
    LEFT JOIN produtos p ON c.produto_id = p.id 
    ORDER BY c.data_cadastro DESC
");
$clientes = $stmt->fetchAll();

// Buscar produtos para o formulário
$stmt = $pdo->query("SELECT id, nome, valor FROM produtos WHERE ativo = 1 ORDER BY nome");
$produtos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Clientes - Sistema de Cobrança</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', sans-serif;
        }
        
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
        
        .status-badge {
            font-size: 11px;
            font-weight: 600;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
        }
        
        .btn {
            border-radius: 8px;
        }
    </style>
</head>

<body>
    <div class="container mt-4">
        <!-- Header da Página -->
        <div class="page-header">
            <h2><i class="fas fa-users me-2"></i>Gerenciar Clientes</h2>
            <p class="mb-0">Cadastro e controle de clientes do sistema</p>
        </div>
        
        <!-- Mensagens -->
        <?php if ($mensagem): ?>
            <div class="alert alert-<?php echo $tipo_mensagem; ?> alert-dismissible fade show">
                <?php echo $mensagem; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Formulário de Adicionar Cliente -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5><i class="fas fa-plus me-2"></i>Adicionar Novo Cliente</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="acao" value="adicionar">
                    
                    <div class="row">
                        <div class="col-md-3">
                            <label class="form-label">Nome do Cliente *</label>
                            <input type="text" name="nome" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">WhatsApp *</label>
                            <input type="text" name="whatsapp" class="form-control" placeholder="5511999999999" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Grupo ID *</label>
                            <input type="text" name="grupo_id" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Produto *</label>
                            <select name="produto_id" class="form-select" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($produtos as $produto): ?>
                                    <option value="<?php echo $produto['id']; ?>">
                                        <?php echo $produto['nome']; ?> - <?php echo formatMoney($produto['valor']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Adicionar Cliente
                        </button>
                        <button type="reset" class="btn btn-secondary">
                            <i class="fas fa-eraser me-2"></i>Limpar
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Lista de Clientes -->
        <div class="card">
            <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-list me-2"></i>Clientes Cadastrados</h5>
                <span class="badge bg-light text-dark"><?php echo count($clientes); ?> clientes</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Nome</th>
                                <th>WhatsApp</th>
                                <th>Grupo ID</th>
                                <th>Produto</th>
                                <th>Status</th>
                                <th>Cadastro</th>
                                <th>Próx. Venc.</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($clientes)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Nenhum cliente cadastrado ainda.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($clientes as $cliente): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($cliente['nome']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($cliente['whatsapp']); ?></td>
                                        <td><code><?php echo htmlspecialchars($cliente['grupo_id']); ?></code></td>
                                        <td>
                                            <?php echo htmlspecialchars($cliente['produto_nome'] ?? 'N/A'); ?>
                                            <br><small class="text-muted"><?php echo formatMoney($cliente['produto_valor'] ?? 0); ?></small>
                                        </td>
                                        <td>
                                            <?php
                                            $status_class = [
                                                'ativo' => 'bg-success',
                                                'vencido' => 'bg-danger', 
                                                'suspenso' => 'bg-warning text-dark'
                                            ];
                                            ?>
                                            <span class="badge status-badge <?php echo $status_class[$cliente['status']] ?? 'bg-secondary'; ?>">
                                                <?php echo ucfirst($cliente['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatDateBR($cliente['data_cadastro']); ?></td>
                                        <td><?php echo formatDateBR($cliente['proximo_vencimento']); ?></td>
                                        <td>
                                            <button class="btn btn-warning btn-sm" onclick="editarCliente(<?php echo htmlspecialchars(json_encode($cliente)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-danger btn-sm" onclick="excluirCliente(<?php echo $cliente['id']; ?>, '<?php echo htmlspecialchars($cliente['nome']); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<!-- Modal de Edição -->
<div class="modal fade" id="modalEditar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Editar Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="editar">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Nome do Cliente *</label>
                        <input type="text" name="nome" id="edit_nome" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">WhatsApp *</label>
                        <input type="text" name="whatsapp" id="edit_whatsapp" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Grupo ID *</label>
                        <input type="text" name="grupo_id" id="edit_grupo_id" class="form-control" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Produto *</label>
                            <select name="produto_id" id="edit_produto_id" class="form-select" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($produtos as $produto): ?>
                                    <option value="<?php echo $produto['id']; ?>">
                                        <?php echo $produto['nome']; ?> - <?php echo formatMoney($produto['valor']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status *</label>
                            <select name="status" id="edit_status" class="form-select" required>
                                <option value="ativo">Ativo</option>
                                <option value="vencido">Vencido</option>
                                <option value="suspenso">Suspenso</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save me-2"></i>Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Confirmação de Exclusão -->
<div class="modal fade" id="modalExcluir" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-trash me-2"></i>Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir o cliente:</p>
                <p><strong id="nomeExcluir"></strong></p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Esta ação não pode ser desfeita!
                </div>
            </div>
            <div class="modal-footer">
                <form method="POST">
                    <input type="hidden" name="acao" value="excluir">
                    <input type="hidden" name="id" id="excluir_id">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>Excluir Cliente
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Função para editar cliente
    function editarCliente(cliente) {
        // Preencher campos do modal com dados do cliente
        document.getElementById('edit_id').value = cliente.id;
        document.getElementById('edit_nome').value = cliente.nome;
        document.getElementById('edit_whatsapp').value = cliente.whatsapp;
        document.getElementById('edit_grupo_id').value = cliente.grupo_id;
        document.getElementById('edit_produto_id').value = cliente.produto_id;
        document.getElementById('edit_status').value = cliente.status;
        
        // Mostrar modal de edição
        const modal = new bootstrap.Modal(document.getElementById('modalEditar'));
        modal.show();
    }
    
    // Função para excluir cliente
    function excluirCliente(id, nome) {
        // Preencher dados no modal de confirmação
        document.getElementById('excluir_id').value = id;
        document.getElementById('nomeExcluir').textContent = nome;
        
        // Mostrar modal de confirmação
        const modal = new bootstrap.Modal(document.getElementById('modalExcluir'));
        modal.show();
    }
    
    // Validação de WhatsApp - apenas números
    document.querySelector('input[name="whatsapp"]').addEventListener('input', function(e) {
        // Remove todos os caracteres não numéricos
        this.value = this.value.replace(/\D/g, '');
        
        // Limita a 15 dígitos
        if (this.value.length > 15) {
            this.value = this.value.substring(0, 15);
        }
    });
    
    // Validação de WhatsApp no modal de edição
    document.getElementById('edit_whatsapp').addEventListener('input', function(e) {
        this.value = this.value.replace(/\D/g, '');
        if (this.value.length > 15) {
            this.value = this.value.substring(0, 15);
        }
    });
    
    // Validação de Grupo ID - remove caracteres especiais
    document.querySelector('input[name="grupo_id"]').addEventListener('input', function(e) {
        this.value = this.value.replace(/[^a-zA-Z0-9@._-]/g, '');
    });
    
    document.getElementById('edit_grupo_id').addEventListener('input', function(e) {
        this.value = this.value.replace(/[^a-zA-Z0-9@._-]/g, '');
    });
    
    // Auto-dismissal de alertas após 5 segundos
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert-dismissible');
        alerts.forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
    
    // Busca em tempo real na tabela de clientes
    if (document.querySelectorAll('tbody tr').length > 1) {
        // Adicionar campo de busca apenas se houver clientes
        const buscaHTML = `
            <div class="p-3 border-bottom">
                <div class="row">
                    <div class="col-md-6">
                        <input type="text" id="buscarCliente" class="form-control" 
                               placeholder="🔍 Buscar por nome, WhatsApp ou Grupo ID...">
                    </div>
                </div>
            </div>
        `;
        
        const tableContainer = document.querySelector('.table-responsive');
        tableContainer.insertAdjacentHTML('beforebegin', buscaHTML);
        
        // Implementar a funcionalidade de busca
        document.getElementById('buscarCliente').addEventListener('input', function(e) {
            const termo = e.target.value.toLowerCase();
            const linhas = document.querySelectorAll('tbody tr');
            
            linhas.forEach(function(linha) {
                if (linha.querySelector('td') && !linha.querySelector('td[colspan]')) {
                    // É uma linha de dados (não é a linha de "nenhum cliente")
                    const texto = linha.textContent.toLowerCase();
                    if (texto.includes(termo)) {
                        linha.style.display = '';
                    } else {
                        linha.style.display = 'none';
                    }
                }
            });
        });
    }
    
    // Confirmação adicional antes de excluir
    document.querySelectorAll('form[method="POST"]').forEach(function(form) {
        if (form.querySelector('input[value="excluir"]')) {
            form.addEventListener('submit', function(e) {
                if (!confirm('Tem CERTEZA absoluta que deseja excluir este cliente?\n\nEsta ação irá remover também todos os pagamentos e lembretes relacionados!')) {
                    e.preventDefault();
                }
            });
        }
    });
    
    // Validação do formulário antes de enviar
    document.querySelector('form[method="POST"]').addEventListener('submit', function(e) {
        const acao = this.querySelector('input[name="acao"]').value;
        
        if (acao === 'adicionar' || acao === 'editar') {
            const nome = this.querySelector('input[name="nome"]').value.trim();
            const whatsapp = this.querySelector('input[name="whatsapp"]').value.trim();
            const grupo_id = this.querySelector('input[name="grupo_id"]').value.trim();
            const produto_id = this.querySelector('select[name="produto_id"]').value;
            
            if (!nome || !whatsapp || !grupo_id || !produto_id) {
                alert('Por favor, preencha todos os campos obrigatórios!');
                e.preventDefault();
                return;
            }
            
            if (whatsapp.length < 10 || whatsapp.length > 15) {
                alert('WhatsApp deve ter entre 10 e 15 dígitos!');
                e.preventDefault();
                return;
            }
        }
    });
    
    // Foco automático no primeiro campo ao abrir modal
    document.getElementById('modalEditar').addEventListener('shown.bs.modal', function() {
        document.getElementById('edit_nome').focus();
    });
    
</script>
</body>
</html>