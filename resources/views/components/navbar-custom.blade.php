<nav class="fixed top-0 z-50 w-full bg-matisse-700 border-b border-gray-200">
    <div class="px-3 py-3 lg:px-5 lg:pl-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center justify-start rtl:justify-end">
                <button data-drawer-target="logo-sidebar" data-drawer-toggle="logo-sidebar" aria-controls="logo-sidebar"
                    type="button"
                    class="inline-flex items-center p-2 text-sm text-gray-500 rounded-lg sm:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200">
                    <span class="sr-only">Open sidebar</span>
                    <svg class="w-6 h-6" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20"
                        xmlns="http://www.w3.org/2000/svg">
                        <path clip-rule="evenodd" fill-rule="evenodd"
                            d="M2 4.75A.75.75 0 012.75 4h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 4.75zm0 10.5a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5a.75.75 0 01-.75-.75zM2 10a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 10z">
                        </path>
                    </svg>
                </button>

                <div class="flex items-center">
                    <x-application-logo wire:ignore class="block h-12 w-12" />

                    <span class="self-center text-xl font-semibold hidden sm:inline whitespace-nowrap text-white">
                        Gonzalez</span>
                </div>



            </div>
            <!-- AVATAR -->
            <div class="flex items-center">
                <div class="flex items-center ms-3">
                    <div class="flex items-center pr-4">
                        <!-- Account Management -->
                        <x-dropdown align="right" width="48">
                            <x-slot name="content">
                                <div class="px-4 py-3 text-sm text-gray-900">
                                    <div class="font-bold truncate">{{ Auth::user()->name }}</div>
                                    <div class="font-normal truncate">{{ Auth::user()->email }}</div>
                                </div>

                                <x-dropdown-link href="{{ route('profile.show') }}">
                                    {{ __('Profile') }}
                                </x-dropdown-link>

                                @if (Laravel\Jetstream\Jetstream::hasApiFeatures())
                                    <x-dropdown-link href="{{ route('api-tokens.index') }}">
                                        {{ __('API Tokens') }}
                                    </x-dropdown-link>
                                @endif

                                <div class="border-t border-gray-200"></div>

                                <!-- Authentication -->
                                <form method="POST" action="{{ route('logout') }}" x-data>
                                    @csrf

                                    <x-dropdown-link href="{{ route('logout') }}" @click.prevent="$root.submit();">
                                        {{ __('Log Out') }}
                                    </x-dropdown-link>
                                </form>
                            </x-slot>
                            <x-slot name="trigger">
                                <button>
                                    <div class="space-y-6">
                                        <div>
                                            <img class="w-10 h-10 p-1 rounded-full ring-2 ring-green-500"
                                                src="{{ Auth::user()->profile_photo_url }}" alt="Avatar">
                                        </div>
                                    </div>
                                </button>
                            </x-slot>
                        </x-dropdown>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

<aside id="logo-sidebar"
    class="fixed top-0 left-0 z-40 w-56 h-screen pt-20 transition-transform -translate-x-full bg-white border-r border-gray-200 sm:translate-x-0"
    aria-label="Sidebar">
    <div class="h-full px-3 pb-4 overflow-y-auto">
        <ul class="space-y-2 font-medium">
            <li>
                <a href="{{ route('dashboard') }}"
                    class="flex items-center p-2 text-black rounded-lg hover:bg-matisse-200 group">
                    <x-hugeicons-home-05 />
                    <span class="ms-3">Inicio</span>
                </a>
            </li>
            <li>
                <a href="{{ route('administracion.index') }}"
                    class="flex items-center p-2 text-black rounded-lg  hover:bg-matisse-200 group">
                    <x-hugeicons-user-add-01 />
                    <span class="flex-1 ms-3 whitespace-nowrap">Administración</span>
                </a>
            </li>
            <li>
                <a href="{{ route('customers.index') }}"
                    class="flex items-center p-2 text-black rounded-lg  hover:bg-matisse-200 group">
                    <x-hugeicons-user-group />
                    <span class="flex-1 ms-3 whitespace-nowrap">Clientes</span>
                </a>
            </li>
            @if (auth()->user()?->isAdmin())
                <li>
                    <a href="{{ route('time.admin.dashboard') }}"
                        class="flex items-center p-2 text-black rounded-lg  hover:bg-matisse-200 group">
                        <x-hugeicons-clock-01 />
                        <span class="flex-1 ms-3 whitespace-nowrap">Supervisión de horas</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('time.admin.profiles') }}"
                        class="flex items-center p-2 text-black rounded-lg  hover:bg-matisse-200 group">
                        <x-hugeicons-user-group />
                        <span class="flex-1 ms-3 whitespace-nowrap">Perfiles organizacionales</span>
                    </a>
                </li>
            @else
                <li>
                    <a href="{{ route('time.index') }}"
                        class="flex items-center p-2 text-black rounded-lg  hover:bg-matisse-200 group">
                        <x-hugeicons-clock-01 />
                        <span class="flex-1 ms-3 whitespace-nowrap">Control de horas</span>
                    </a>
                </li>
            @endif
        </ul>
    </div>
</aside>
