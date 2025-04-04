<x-layouts.main-layout pageTitle='Home'>


    <div class="container-fluid mt-5">
        <div class="row">
            <div class="col p-3">
                <p class="text-center display-6">Seja bem-vindo, <strong>{{ Auth::user()->username }}</strong></p>
            </div>
        </div>
    </div>


</x-layouts.main-layout>
