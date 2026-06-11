<div class="min-h-screen w-full bg-cover bg-center bg-no-repeat"
    style="background-image: url('/img/auth/shiny-overlay.svg');">


    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
        <div>
            {{ $logo }}
        </div>

        <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden">
            {{ $slot }}
        </div>

        <div class="mt-20 text-matisse-100">
            <a href="https://datamid.com.mx/">
                Copyright © DataMID 2025
            </a>

        </div>
    </div>
</div>