<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Beleza CRM — seu negócio organizado</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gradient-to-b from-violet-50 via-white to-white text-zinc-900">
    <header class="mx-auto flex max-w-7xl items-center justify-between px-5 py-6 lg:px-8">
        <a href="/" class="flex items-center gap-3 font-bold"><span class="flex size-10 items-center justify-center rounded-xl bg-violet-600 text-white"><x-app-logo-icon class="size-6" /></span><span>Beleza CRM</span></a>
        <nav class="flex items-center gap-3">@auth<a href="{{ auth()->user()->currentTeam ? route('dashboard') : route('onboarding.establishment') }}" class="rounded-xl bg-violet-600 px-4 py-2 text-sm font-semibold text-white">{{ auth()->user()->currentTeam ? 'Abrir painel' : 'Continuar cadastro' }}</a>@else<a href="{{ route('login') }}" class="px-3 py-2 text-sm font-semibold text-zinc-700">Entrar</a><a href="{{ route('register') }}" class="rounded-xl bg-violet-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-violet-200">Assinar</a>@endauth</nav>
    </header>
    <main>
        <section class="mx-auto grid max-w-7xl items-center gap-12 px-5 py-16 lg:grid-cols-2 lg:px-8 lg:py-24">
            <div><span class="rounded-full bg-violet-100 px-3 py-1 text-sm font-semibold text-violet-700">Feito para pequenos negócios de beleza</span><h1 class="mt-6 text-4xl font-bold tracking-tight sm:text-6xl">Mais tempo para cuidar das suas clientes.</h1><p class="mt-6 max-w-xl text-lg leading-8 text-zinc-600">Organize agenda, clientes, serviços e financeiro em um só lugar — simples no celular e completo para sua equipe.</p><div class="mt-8 flex flex-wrap gap-3"><a href="{{ route('register') }}" class="rounded-xl bg-violet-600 px-6 py-3 font-semibold text-white shadow-xl shadow-violet-200">Assinar agora</a><a href="#recursos" class="rounded-xl border border-violet-200 bg-white px-6 py-3 font-semibold text-violet-700">Conhecer recursos</a></div></div>
            <div class="relative"><div class="absolute -inset-8 rounded-full bg-violet-200/50 blur-3xl"></div><div class="relative rounded-3xl border border-violet-100 bg-white p-5 shadow-2xl shadow-violet-200/60"><div class="flex items-center justify-between border-b border-violet-50 pb-4"><div><p class="text-sm text-zinc-500">Agenda de hoje</p><p class="text-xl font-bold">5 atendimentos</p></div><span class="rounded-xl bg-violet-100 px-3 py-2 text-sm font-semibold text-violet-700">Ter, 02 ago</span></div><div class="mt-4 space-y-3">@foreach([['09:00','Mariana','Design de sobrancelha'],['11:00','Camila','Maquiagem social'],['14:30','Ana','Corte + escova']] as [$time,$name,$service])<div class="flex items-center gap-4 rounded-2xl bg-violet-50/70 p-4"><strong class="text-violet-700">{{ $time }}</strong><div><p class="font-semibold">{{ $name }}</p><p class="text-sm text-zinc-500">{{ $service }}</p></div></div>@endforeach</div></div></div>
        </section>
        <section id="recursos" class="mx-auto max-w-7xl px-5 py-16 lg:px-8"><div class="grid gap-4 md:grid-cols-3">@foreach([['Agenda sem conflitos','Horários, profissionais e serviços organizados com segurança.'],['Clientes por perto','Contatos e histórico compartilhados com a equipe.'],['Financeiro claro','Acompanhe recebimentos, despesas e saldo do mês.']] as [$title,$text])<article class="rounded-2xl border border-violet-100 bg-white p-6 shadow-sm"><div class="mb-4 size-10 rounded-xl bg-violet-100"></div><h2 class="text-lg font-bold">{{ $title }}</h2><p class="mt-2 text-zinc-600">{{ $text }}</p></article>@endforeach</div></section>
    </main>
</body>
</html>
