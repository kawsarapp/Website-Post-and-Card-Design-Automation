<script>
templates.magazine_cover = `
    <div class="h-full w-full relative bg-white">
        <img id="cardImage" src="" class="absolute inset-0 w-full h-full object-cover">
        <div class="absolute inset-0 bg-gradient-to-b from-black/70 via-transparent to-black/90"></div>
        <div class="absolute top-0 w-full p-12 text-center border-b border-white/20 backdrop-blur-sm bg-black/10">
                <h2 class="text-[120px] font-black text-white leading-none tracking-tighter opacity-20 absolute top-2 left-0 w-full select-none">MAGAZINE</h2>
                <span class="relative text-5xl font-bold text-white uppercase tracking-[1em]" id="brandNameDisplay">BRAND</span>
        </div>
        <div class="absolute bottom-0 w-full p-16 pb-24 flex flex-col items-center text-center">
            <div class="bg-white text-black text-xl font-bold px-6 py-2 uppercase tracking-widest mb-6" id="textBadge">Feature Story</div>
            <div id="logoWrapper" class="hidden mb-6"><img id="logoImg" src="" class="h-20 w-auto bg-white p-2"></div>
            <h1 id="cardTitle" class="text-[70px] font-black text-white font-['Hind_Siliguri'] leading-[1.1] mb-4 drop-shadow-2xl">Title Here</h1>
            <div class="w-32 h-2 bg-red-600 my-6"></div>
            <p class="text-gray-300 text-2xl" id="currentDate">Date</p>
        </div>
    </div>`;
</script>