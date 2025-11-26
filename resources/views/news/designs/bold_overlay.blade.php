<script>
templates.bold_overlay = `
    <div class="relative h-full w-full overflow-hidden bg-black">
        <img id="cardImage" src="" class="absolute inset-0 w-full h-full object-cover opacity-80">
        <div class="absolute inset-0 bg-gradient-to-t from-black via-black/60 to-transparent"></div>
        <div class="absolute top-10 right-10">
                <div id="textBadge" class="bg-red-700 text-white px-6 py-3 text-3xl font-bold uppercase -skew-x-12 shadow-2xl border-l-4 border-white">BREAKING</div>
                <div id="logoWrapper" class="hidden"><img id="logoImg" src="" class="h-24 w-auto drop-shadow-lg"></div>
        </div>
        <div class="absolute bottom-0 w-full p-20 flex flex-col items-start justify-end h-full">
            <div class="bg-red-600 text-white font-bold px-4 py-1 text-xl mb-4 uppercase tracking-widest inline-block">Top Story</div>
            <div id="textWrapper" class="w-full">
                <h1 id="cardTitle" class="text-[75px] font-black text-white leading-tight font-['Hind_Siliguri'] drop-shadow-2xl text-left border-l-8 border-red-600 pl-8">Title Here</h1>
            </div>
            <div class="w-full flex justify-between items-center mt-12 text-gray-300 border-t border-white/20 pt-6 font-['Hind_Siliguri']">
                <span class="text-4xl font-bold text-white tracking-widest uppercase" id="brandNameDisplay">Brand</span>
                <span class="text-3xl font-light" id="currentDate">Date</span>
            </div>
        </div>
    </div>`;
</script>