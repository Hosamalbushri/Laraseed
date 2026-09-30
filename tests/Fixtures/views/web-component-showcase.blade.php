@extends('web::layouts.master')

@section('content')
    <div class="web-container py-8 space-y-8" data-web-component-showcase>
        <header class="space-y-2">
            <x-web::badge variant="info">Public Web Component Kernel</x-web::badge>
            <h1 class="text-3xl font-bold">Blade, Vue, Tailwind, and Vite</h1>
            <p data-showcase-server-content>This content is rendered by Laravel before Vue initializes.</p>
        </header>

        <x-web::card id="showcase-static-components">
            <x-web::card.header>Static components</x-web::card.header>
            <x-web::card.content>
                <div class="flex flex-wrap gap-3">
                    <x-web::button>Primary action</x-web::button>
                    <x-web::button variant="outline">Outline action</x-web::button>
                    <x-web::button disabled>Disabled action</x-web::button>
                    <x-web::badge variant="success">Ready</x-web::badge>
                </div>

                <x-web::form.field id="showcase-email" label="Email" help="Standard attributes pass through the public API.">
                    <x-web::form.input id="showcase-email" name="email" type="email" autocomplete="email" describedBy="showcase-email-help" />
                </x-web::form.field>
            </x-web::card.content>
        </x-web::card>

        <x-web::alert title="Progressive enhancement" :dismissible="true">
            The Blade content and accessible disclosure structure exist without JavaScript.
        </x-web::alert>

        <section aria-labelledby="single-accordion-heading" class="space-y-3">
            <h2 id="single-accordion-heading" class="text-2xl font-bold">Single-open accordion</h2>
            <x-web::accordion id="showcase-accordion-one">
                <x-web::accordion.item id="showcase-one-a" title="First question" :expanded="true">
                    First answer remains server rendered.
                </x-web::accordion.item>
                <x-web::accordion.item id="showcase-one-b" title="Second question">
                    Second answer is independently controlled.
                </x-web::accordion.item>
            </x-web::accordion>
        </section>

        <section aria-labelledby="multiple-accordion-heading" class="space-y-3">
            <h2 id="multiple-accordion-heading" class="text-2xl font-bold">Multiple-open accordion</h2>
            <x-web::accordion id="showcase-accordion-two" :alwaysOpen="true" flush data-showcase-second-accordion>
                <x-web::accordion.item id="showcase-two-a" title="Third question">
                    Third answer belongs to the second instance.
                </x-web::accordion.item>
                <x-web::accordion.item id="showcase-two-b" title="Fourth question">
                    Fourth answer can stay open with the third.
                </x-web::accordion.item>
            </x-web::accordion>
        </section>

        {{-- Modals Showcase --}}
        <section aria-labelledby="modals-heading" class="space-y-3">
            <h2 id="modals-heading" class="text-2xl font-bold">Modals (Independent Instances)</h2>
            <div class="flex flex-wrap gap-4">
                <x-web::modal id="showcase-modal-one" title="Modal One Title" size="md">
                    <x-slot:trigger>
                        <x-web::button id="trigger-modal-one">Open Modal One</x-web::button>
                    </x-slot:trigger>
                    <p>Modal One content is completely independent.</p>
                    <x-slot:footer>
                        <x-web::button variant="outline" data-web-modal-close>Cancel</x-web::button>
                        <x-web::button variant="primary">Confirm Action</x-web::button>
                    </x-slot:footer>
                </x-web::modal>

                <x-web::modal id="showcase-modal-two" title="Modal Two Title" size="lg">
                    <x-slot:trigger>
                        <x-web::button variant="outline" id="trigger-modal-two">Open Modal Two</x-web::button>
                    </x-slot:trigger>
                    <p>Modal Two content is larger and also independent.</p>
                </x-web::modal>
            </div>
        </section>

        {{-- Drawers Showcase --}}
        <section aria-labelledby="drawers-heading" class="space-y-3">
            <h2 id="drawers-heading" class="text-2xl font-bold">Drawers (Independent Instances)</h2>
            <div class="flex flex-wrap gap-4">
                <x-web::drawer id="showcase-drawer-start" title="Start Drawer" placement="start">
                    <x-slot:trigger>
                        <x-web::button id="trigger-drawer-start">Open Start Drawer</x-web::button>
                    </x-slot:trigger>
                    <p>Start drawer slides from start edge respecting LTR/RTL.</p>
                </x-web::drawer>

                <x-web::drawer id="showcase-drawer-end" title="End Drawer" placement="end">
                    <x-slot:trigger>
                        <x-web::button variant="outline" id="trigger-drawer-end">Open End Drawer</x-web::button>
                    </x-slot:trigger>
                    <p>End drawer slides from end edge respecting LTR/RTL.</p>
                </x-web::drawer>
            </div>
        </section>

        {{-- Dropdowns Showcase --}}
        <section aria-labelledby="dropdowns-heading" class="space-y-3">
            <h2 id="dropdowns-heading" class="text-2xl font-bold">Dropdowns (Independent Instances)</h2>
            <div class="flex flex-wrap gap-4">
                <x-web::dropdown id="showcase-dropdown-one" align="start">
                    <x-slot:trigger>
                        <x-web::button id="trigger-dropdown-one">Options Menu</x-web::button>
                    </x-slot:trigger>
                    <div class="py-1">
                        <a href="#action1" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-100 rounded-lg">Action 1</a>
                        <a href="#action2" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-100 rounded-lg">Action 2</a>
                    </div>
                </x-web::dropdown>

                <x-web::dropdown id="showcase-dropdown-two" align="end">
                    <x-slot:trigger>
                        <x-web::button variant="outline" id="trigger-dropdown-two">Profile Menu</x-web::button>
                    </x-slot:trigger>
                    <div class="py-1">
                        <a href="#profile" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-100 rounded-lg">Profile</a>
                        <a href="#settings" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-100 rounded-lg">Settings</a>
                    </div>
                </x-web::dropdown>
            </div>
        </section>
    </div>
@endsection
