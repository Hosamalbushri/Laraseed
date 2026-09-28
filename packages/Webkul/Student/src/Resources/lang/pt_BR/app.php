<?php

return [
    'acl' => [
        'students' => 'Estudantes',
        'create' => 'Criar',
        'edit' => 'Editar',
        'view' => 'Visualizar',
        'delete' => 'Excluir',
    ],

    'students' => [
        'title' => 'Estudantes',
        'create-success' => 'Estudante criado com sucesso.',
        'update-success' => 'Estudante atualizado com sucesso.',
        'delete-success' => 'Estudante excluído com sucesso.',
        'delete-failed' => 'Falha ao excluir o estudante.',
        'all-delete-success' => 'Estudantes selecionados excluídos com sucesso.',
        'no-selection' => 'Nenhum estudante foi selecionado.',

        'index' => [
            'title' => 'Estudantes',
            'create-btn' => 'Adicionar Estudante',

            'datagrid' => [
                'id' => 'ID',
                'name' => 'Nome',
                'university-card-number' => 'Número do Cartão Universitário',
                'registration-number' => 'Número de Matrícula',
                'major' => 'Curso',
                'academic-level' => 'Nível Acadêmico',
                'created-at' => 'Criado em',
                'view' => 'Visualizar',
                'edit' => 'Editar',
                'delete' => 'Excluir',
            ],
        ],

        'create' => [
            'title' => 'Adicionar Estudante',
            'save-btn' => 'Salvar Estudante',
        ],

        'edit' => [
            'title' => 'Editar Estudante',
            'save-btn' => 'Salvar Alterações',
        ],

        'view' => [
            'title' => 'Estudante: :name',
            'heading' => 'Detalhes do Estudante',
            'edit-btn' => 'Editar Estudante',
            'general-info' => 'Informações Gerais',
        ],

        'form' => [
            'name' => 'Nome',
            'university-card-number' => 'Número do Cartão Universitário',
            'registration-number' => 'Número de Matrícula',
            'major' => 'Curso',
            'academic-level' => 'Nível Acadêmico',
            'password' => 'Senha',
            'password-confirmation' => 'Confirmar Senha',
            'profile-image' => 'Imagem de Perfil',
        ],
    ],

    'configuration' => [
        'student-login' => [
            'title' => 'Página de Login do Estudante',
            'info' => 'Configurar a identidade visual e conteúdo do portal de login.',
            'logo-image' => 'Logo de Login',
            'primary-color' => 'Cor Primária',
            'accent-color' => 'Cor de Destaque',
            'surface-start' => 'Início do Gradiente de Superfície',
            'surface-end' => 'Fim do Gradiente de Superfície',
            'panel-start' => 'Início do Gradiente do Painel Lateral',
            'panel-end' => 'Fim do Gradiente do Painel Lateral',
            'field-title' => 'Título do Cabeçalho',
            'description' => 'Descrição do Cabeçalho',
            'eyebrow' => 'Texto Introdutório',
            'panel-lead' => 'Parágrafo Principal do Painel Lateral',
            'card-number' => 'Rótulo do Número do Cartão',
            'password' => 'Rótulo da Senha',
            'remember' => 'Rótulo de Lembrar-me',
            'submit' => 'Rótulo do Botão de Envio',
            'back-portal' => 'Rótulo do Botão Voltar',
        ],

        'university-api' => [
            'title' => 'Integração com API Universitária',
            'info' => 'Configurar endpoint de verificação remota de estudantes.',
            'endpoint-settings' => [
                'title' => 'Configurações de Endpoint',
                'info' => 'Configurar o URL do endpoint de verificação.',
                'endpoint' => 'URL do Endpoint de Verificação',
                'endpoint-info' => 'Insira a URL completa para verificação de estudantes.',
            ],
        ],
    ],

    'components' => [
        'layouts' => [
            'header' => [
                'mega-search' => [
                    'explore-all-students' => 'Explorar todos os Estudantes',
                ],
            ],
        ],
    ],

    'login' => [
        'title' => 'Login do Estudante',
        'description' => 'Use o número do seu cartão universitário e a senha emitida pela instituição.',
        'eyebrow' => 'Acesso seguro',
        'panel_title' => 'Seu portal acadêmico',
        'panel_lead' => 'Um lugar unificado para eventos, novidades e tudo que você precisa.',
        'feature_verify' => 'Identidade verificada pela universidade no primeiro acesso',
        'feature_profile' => 'Perfil sincronizado com o registro oficial',
        'feature_portal' => 'Acesso contínuo ao portal do estudante',
        'trust_note' => 'As credenciais são verificadas com a sua instituição.',
        'back_portal' => 'Voltar para o início',
        'show_password' => 'Mostrar senha',
        'hide_password' => 'Ocultar senha',
        'card_number' => 'Número do cartão universitário',
        'password' => 'Senha',
        'remember' => 'Lembrar-me',
        'submit' => 'Entrar',
        'failed' => 'Essas credenciais não coincidem com nossos registros.',
        'welcome_back' => 'Bem-vindo de volta.',
        'registered' => 'Sua conta foi criada. Bem-vindo.',
        'logged_out' => 'Você saiu com sucesso.',
    ],

    'university' => [
        'unavailable' => 'O serviço de verificação universitária está indisponível.',
        'invalid_credentials' => 'A universidade não aceitou estas credenciais.',
        'invalid_response' => 'Resposta inesperada da universidade.',
    ],
];
