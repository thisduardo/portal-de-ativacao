<?php

/**
 * ARQUIVO: index.php
 * VERSÃO: 4.5 - Benefícios sempre ATIVOS + remove botão ativar
 * DESCRIÇÃO: Remove qualquer CTA de “Ativar Benefício”.
 * Os benefícios que o usuário tem direito aparecem como “Ativo” ao logar.
 */

$pageTitle = "TKS Vantagens - Ativação";
$currentYear = date('Y');

// DADOS DO USUÁRIO
$userName = "--";
$userLastName = "--";
$userCompany = "--";
$userInitials = "--";
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>

    <!-- 1. Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- 2. Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- 3. Google Fonts: SORA -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- 4. CSS Local -->
    <link rel="stylesheet" href="home.css">
</head>

<body class="min-h-screen flex flex-col items-center justify-center font-sora bg-slate-50 text-slate-800 p-4 relative overflow-x-hidden">

    <!-- FUNDO -->
    <div class="fixed top-[-20%] right-[-10%] w-[50vw] h-[50vw] bg-tks-primary/5 rounded-full blur-[100px] pointer-events-none z-0 anim-float-slow"></div>
    <div class="fixed bottom-[-20%] left-[-10%] w-[50vw] h-[50vw] bg-blue-100/50 rounded-full blur-[100px] pointer-events-none z-0 anim-float-reverse"></div>

    <main class="w-full max-w-5xl relative z-10 flex flex-col items-center">

        <!-- TELA 1: LOGIN -->
        <div id="screen-login" class="w-full max-w-md flex flex-col items-center">

            <div class="mb-8 anim-slide-down delay-100">
                <img src="https://api.tksvantagens.com.br/storage/v1/object/public/emailmkt//logonova.png"
                    alt="TKS Vantagens"
                    class="h-16 w-auto object-contain drop-shadow-sm hover:scale-105 transition-transform duration-500 cursor-pointer">
            </div>

            <div class="w-full bg-white rounded-[2rem] shadow-clean p-10 relative overflow-hidden anim-fade-in-up delay-200 hover:shadow-clean-lg transition-shadow duration-500">
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-tks-primary to-purple-400"></div>

                <div class="text-center mb-10">
                    <h2 class="text-2xl font-bold text-slate-800 mb-2">Bem-vindo</h2>
                    <p class="text-slate-400 text-sm">Digite seu CPF para acessar seus benefícios.</p>
                </div>

                <form id="form-check-cpf" class="space-y-6">
                    <div class="group">
                        <label for="cpf" class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 ml-1 transition-colors group-focus-within:text-tks-primary">CPF do Titular</label>
                        <div class="relative transform transition-transform duration-200 group-focus-within:scale-[1.01]">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fas fa-id-card text-slate-300 group-focus-within:text-tks-primary transition-colors text-lg duration-300"></i>
                            </div>
                            <input type="text" name="cpf" id="cpf"
                                class="block w-full pl-12 pr-4 py-4 bg-slate-50 border border-slate-200 text-slate-800 rounded-xl focus:bg-white focus:ring-4 focus:ring-tks-primary/10 focus:border-tks-primary transition-all outline-none text-lg font-medium placeholder-slate-300"
                                placeholder="000.000.000-00"
                                maxlength="14"
                                autocomplete="off">
                        </div>
                    </div>

                    <button type="submit" id="btn-verify" class="btn-press w-full py-4 rounded-xl font-bold text-white bg-tks-primary hover:bg-tks-dark shadow-lg shadow-tks-primary/20 hover:shadow-tks-primary/40 transition-all duration-300 flex justify-center items-center gap-2 group">
                        <span id="btn-text">Continuar</span>
                        <i class="fas fa-arrow-right text-xs opacity-70 group-hover:translate-x-1 transition-transform"></i>
                        <span id="btn-loader" class="hidden">
                            <i class="fas fa-circle-notch fa-spin"></i>
                        </span>
                    </button>


                </form>
            </div>

            <p class="mt-8 text-xs text-slate-400 font-medium anim-fade-in delay-500">© <?php echo $currentYear; ?> TKS Vantagens. Todos os direitos reservados.</p>
        </div>

        <!-- TELA 2: DASHBOARD -->
        <div id="screen-dashboard" class="w-full hidden flex-col items-center">

            <div class="mb-6 lg:mb-10 anim-slide-down">
                <img src="https://api.tksvantagens.com.br/storage/v1/object/public/emailmkt//logonova.png"
                    alt="TKS Vantagens"
                    class="h-10 lg:h-12 w-auto object-contain opacity-90 hover:opacity-100 transition-opacity">
            </div>

            <!-- GRID PRINCIPAL -->
            <div class="w-full grid grid-cols-1 lg:grid-cols-3 gap-4 lg:gap-8">

                <!-- COLUNA 1: PERFIL -->
                <div class="lg:col-span-1 anim-fade-in-up delay-100 flex flex-col">
                    <div class="bg-white rounded-[1.5rem] lg:rounded-[2rem] shadow-clean p-5 lg:p-8 text-left lg:text-center hover:shadow-clean-lg transition-shadow duration-300 h-full flex flex-col justify-between">

                        <div class="flex flex-col gap-1 lg:block">

                            <div class="hidden lg:flex w-24 h-24 mx-auto rounded-full bg-slate-50 items-center justify-center text-tks-primary text-3xl font-bold border border-slate-100 mb-6 shadow-sm relative group cursor-default">
                                <div class="absolute inset-0 rounded-full border-2 border-tks-primary/10 scale-100 group-hover:scale-110 transition-transform duration-500"></div>
                                <span id="user-initials-display" class="relative z-10"><?php echo $userInitials; ?></span>
                            </div>

                            <div class="flex items-center gap-3 lg:block lg:mb-1">
                                <h1 class="text-lg lg:text-xl font-bold text-slate-800">Olá, <span id="user-name" class="text-tks-primary"><?php echo $userName; ?></span></h1>

                                <div class="inline-flex items-center gap-1.5 bg-green-50 px-2.5 py-0.5 lg:px-3 lg:py-1 rounded-full border border-green-100 cursor-default">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                                    <span class="text-[10px] lg:text-xs font-semibold text-green-700 uppercase tracking-wide">Ativo</span>
                                </div>
                            </div>

                            <div class="border-none pt-0 lg:border-t lg:border-slate-100 lg:pt-6 mt-1 lg:mt-0">
                                <span class="text-xs text-slate-400 font-normal lg:font-bold lg:uppercase lg:tracking-widest mr-1 lg:block lg:mb-2">Empresa:</span>
                                <span class="text-sm font-bold text-slate-600 lg:font-medium" id="user-company"><?php echo $userCompany; ?></span>
                            </div>
                        </div>

                        <button id="btn-logout" class="btn-press mt-3 lg:mt-8 w-full py-2.5 lg:py-3 rounded-xl border border-slate-200 text-slate-500 hover:border-red-200 hover:text-red-500 hover:bg-red-50 transition-all text-xs lg:text-sm font-semibold flex items-center justify-center gap-2 group">
                            <i class="fas fa-sign-out-alt group-hover:-translate-x-1 transition-transform"></i> Sair da conta
                        </button>
                    </div>
                </div>

                <!-- COLUNA 2: BENEFÍCIOS -->
                <div class="lg:col-span-2 flex flex-col gap-4 lg:gap-6">
                    <h3 class="text-base lg:text-lg font-bold text-slate-700 px-2 flex items-center gap-2 anim-fade-in delay-200">
                        <i class="far fa-star text-tks-primary"></i> Seus Benefícios
                    </h3>

                    <!-- CARD 1: CLUBE -->
                    <div id="card-clube" class="btn-card-press anim-fade-in-up delay-200 benefit-card bg-white rounded-[1.5rem] p-5 lg:p-6 shadow-clean border border-transparent hover:border-tks-primary/20 transition-all duration-300 relative overflow-hidden group">
                        <div class="absolute top-0 right-0 w-24 h-24 lg:w-32 lg:h-32 bg-gradient-to-br from-pink-50 to-purple-50 rounded-bl-full -mr-8 -mt-8 opacity-50 group-hover:scale-125 group-hover:rotate-6 transition-transform duration-700"></div>

                        <div class="flex flex-col sm:flex-row gap-4 lg:gap-6 items-start relative z-10">
                            <div class="w-12 h-12 lg:w-16 lg:h-16 rounded-2xl bg-tks-primary/5 flex items-center justify-center text-tks-primary text-xl lg:text-2xl group-hover:bg-tks-primary group-hover:text-white transition-all duration-300 shrink-0 group-hover:rotate-3 group-hover:scale-110 shadow-sm group-hover:shadow-md">
                                <i class="fas fa-tags transform transition-transform group-hover:scale-110"></i>
                            </div>

                            <div class="flex-grow w-full">
                                <h4 class="text-base lg:text-lg font-bold text-slate-800 mb-1 lg:mb-2 group-hover:text-tks-primary transition-colors">Clube de Vantagens</h4>
                                <p class="text-slate-500 text-xs lg:text-sm leading-relaxed mb-4 lg:mb-6">
                                    Acesse descontos exclusivos em milhares de lojas. Economia real no seu dia a dia.
                                </p>

                                <div id="status-clube-area">
                                    <div class="flex items-center gap-4">
                                        <div class="inline-flex items-center gap-2 text-green-600 font-bold text-xs lg:text-sm bg-green-50 px-3 py-1.5 lg:px-4 lg:py-2 rounded-lg border border-green-100 shadow-sm">
                                            <i class="fas fa-check-circle"></i> Ativo
                                        </div>
                                        <a href="#"  id="access-club" class="text-xs lg:text-sm font-semibold text-slate-400 hover:text-tks-primary transition border-b-2 border-transparent hover:border-tks-primary pb-0.5">
                                            Acessar Clube
                                        </a>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- CARD 2: TELEMEDICINA -->
                    <div id="card-tele" class="btn-card-press anim-fade-in-up delay-300 benefit-card bg-white rounded-[1.5rem] p-5 lg:p-6 shadow-clean border border-transparent hover:border-tks-primary/20 transition-all duration-300 relative overflow-hidden group">
                        <div class="absolute top-0 right-0 w-24 h-24 lg:w-32 lg:h-32 bg-gradient-to-br from-blue-50 to-indigo-50 rounded-bl-full -mr-8 -mt-8 opacity-50 group-hover:scale-125 group-hover:rotate-6 transition-transform duration-700"></div>

                        <div class="flex flex-col sm:flex-row gap-4 lg:gap-6 items-start relative z-10">
                            <div class="w-12 h-12 lg:w-16 lg:h-16 rounded-2xl bg-tks-primary/5 flex items-center justify-center text-tks-primary text-xl lg:text-2xl group-hover:bg-tks-primary group-hover:text-white transition-all duration-300 shrink-0 group-hover:-rotate-3 group-hover:scale-110 shadow-sm group-hover:shadow-md">
                                <i class="fas fa-user-md transform transition-transform group-hover:scale-110"></i>
                            </div>

                            <div class="flex-grow w-full">
                                <h4 class="text-base lg:text-lg font-bold text-slate-800 mb-1 lg:mb-2 group-hover:text-tks-primary transition-colors">Telemedicina TKS</h4>
                                <p class="text-slate-500 text-xs lg:text-sm leading-relaxed mb-4 lg:mb-6">
                                    Consultas médicas online 24h. Clínico geral e pediatra sem custo adicional.
                                </p>

                                <div id="status-tele-area">
                                    <div class="flex items-center gap-4">
                                        <div class="inline-flex items-center gap-2 text-green-600 font-bold text-xs lg:text-sm bg-green-50 px-3 py-1.5 lg:px-4 lg:py-2 rounded-lg border border-green-100 shadow-sm">
                                            <i class="fas fa-check-circle"></i> Ativo
                                        </div>
                                        <a href="https://vitavida.dav.med.br/login" class="text-xs lg:text-sm font-semibold text-slate-400 hover:text-tks-primary transition border-b-2 border-transparent hover:border-tks-primary pb-0.5 group-hover:text-tks-primary/70">
                                            Acessar Plataforma
                                        </a>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                    <!-- FIM CARD 2 -->

                </div>
            </div>
        </div>

    </main>

    <!-- MODAIS -->
    <div id="modal-error" class="fixed inset-0 z-50 hidden flex items-center justify-center px-4 bg-slate-900/40 backdrop-blur-sm transition-opacity opacity-0 pointer-events-none" style="transition: opacity 0.3s ease;">
        <div class="bg-white rounded-[2rem] p-8 max-w-sm w-full text-center shadow-2xl transform scale-95 transition-transform duration-300" id="modal-error-content">
            <div class="w-16 h-16 mx-auto bg-red-50 rounded-full flex items-center justify-center text-red-500 text-2xl mb-6 anim-shake">
                <i class="fas fa-times"></i>
            </div>
            <h3 class="text-xl font-bold text-slate-800 mb-2">CPF Não Encontrado</h3>
            <p class="text-slate-500 text-sm mb-8">Não encontramos um cadastro para este documento. Por favor, verifique os números.</p>
            <button onclick="closeModal()" class="btn-press w-full py-3.5 rounded-xl bg-slate-100 text-slate-700 font-bold hover:bg-slate-200 transition">
                Tentar Novamente
            </button>
        </div>
    </div>

    <div id="modal-success" class="fixed inset-0 z-50 hidden flex items-center justify-center px-4 bg-slate-900/40 backdrop-blur-sm transition-opacity opacity-0 pointer-events-none" style="transition: opacity 0.3s ease;">
        <div class="bg-white rounded-[2rem] p-8 max-w-sm w-full text-center shadow-2xl transform scale-95 transition-transform duration-300 relative overflow-hidden" id="modal-success-content">
            <div class="absolute top-0 left-0 w-full h-1 bg-green-500"></div>

            <div class="absolute inset-0 overflow-hidden pointer-events-none">
                <div class="absolute top-4 left-4 w-2 h-2 bg-yellow-400 rounded-full animate-ping"></div>
                <div class="absolute top-10 right-10 w-2 h-2 bg-purple-400 rounded-full animate-ping delay-300"></div>
            </div>

            <div class="w-20 h-20 mx-auto bg-green-50 rounded-full flex items-center justify-center text-green-500 text-3xl mb-6 animate-bounce-short shadow-sm border border-green-100">
                <i class="fas fa-check"></i>
            </div>
            <h3 class="text-2xl font-bold text-slate-800 mb-2">Tudo Pronto!</h3>
            <p class="text-slate-500 text-sm mb-8">
                Seu benefício foi ativado com sucesso. Verifique seu e-mail para as instruções de acesso.
            </p>
            <button onclick="closeSuccessModal()" class="btn-press w-full py-3.5 rounded-xl bg-tks-primary text-white font-bold hover:bg-tks-dark shadow-lg shadow-tks-primary/20 transition hover:shadow-tks-primary/40">
                Entendido
            </button>
        </div>
    </div>

    <script src="script.js"></script>
</body>

</html>
