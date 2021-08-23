<x-frontend-layout>
    <x-slot name="heading">Attributions</x-slot>
    <x-slot name="subHeading">We love these resources!</x-slot>
    
    <ul class="mx-8 space-y-4">
        <li>
            <img src="{{ url('/static/images/icons/bull.png') }}" class="inline-block w-8"> Icon made by <a href="https://www.flaticon.com/authors/dave-gandy" class="underline" title="Dave Gandy">Dave Gandy</a> from <a href="https://www.flaticon.com/" class="underline" title="Flaticon">www.flaticon.com</a>
        </li>
        <li>
            Transaction data retrieved from the <a class="underline" href="https://efdsearch.senate.gov/search/home/">Office of Public Records finanical disclosured website (eFD)</a>.
        </li>
        <li>
            Senator data retrieved from the <a class="underline" href="https://projects.propublica.org/api-docs/congress-api/">ProPublica Congress API</a>.
        </li>
    </ul>
</x-frontend-layout>