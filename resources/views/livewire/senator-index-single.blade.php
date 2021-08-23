<div class="w-1/4 px-8">
    <a href="{{ route('senator.show', $senator->slug) }}">
        <div class="border-2 border-gray-500 p-4 mb-12 rounded-xl bg-gray-200 text-gray-900 w-full max-w-xl transform transition-transform hover:-translate-y-1 text-center">
            <figure
                x-data = "{ 
                    avatar: sessionStorage.getItem('avatar-{{$senator->id}}')
                }"
                x-init = "avatar = avatar || (function(){
                    const avatar = '{{ $senator->getRandomAvatar() }}'
                    sessionStorage.setItem('avatar-{{$senator->id}}', avatar)
                    return avatar
                })()"
                class="w-full mb-4"
            >
                <img class="max-w-full w-36 rounded-full mx-auto" :src="avatar" alt="Profile Image" />
            </figure>
            <p>
                {{ $senator->fullName() }}
            </p>
            <p class="text-{{ $senator->party['symbol'] }}">
                {{ $senator->party['name'] }}
            </p>
            <p>
                {{ $senator->state['name'] }}
            </p>
            <p>
                In Office: {{ $senator->in_office ? 'Yes' : 'No' }}
            </p>
        </div>
    </a>
</div>