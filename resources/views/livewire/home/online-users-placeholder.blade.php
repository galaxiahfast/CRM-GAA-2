{{-- Mientras carga "En línea ahora": mismo encabezado y caja que el componente real, para que al llegar los datos no salte nada --}}
<section class="home-rise flex flex-col xl:absolute xl:inset-x-0 xl:top-0" style="--i: 1" aria-label="Personas en línea" aria-busy="true">
    <header class="home-sec-head2 shrink-0">
        <div class="min-w-0">
            <h2 class="home-h2">En línea ahora</h2>
            <p class="home-muted">Qué hace cada persona y dónde está.</p>
        </div>
    </header>
    <div class="home-online-list min-h-[240px]"></div>
</section>
