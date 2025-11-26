<script>
templates.glass_blur = `
    <div class="relative h-full w-full overflow-hidden bg-gray-800 flex items-center justify-center">
        <img id="cardImage" src="" class="absolute inset-0 w-full h-full object-cover scale-110 filter blur-sm brightness-75">
        <div class="relative w-[90%] bg-white/10 backdrop-blur-xl border border-white/20 p-12 rounded-3xl shadow-2xl flex flex-col items-center text-center">
            <div class="absolute -top-6 bg-gradient-to-r from-purple-600 to-pink-600 text-white px-8 py-3 rounded-full text-2xl font-bold shadow-lg uppercase" id="textBadge">Highlight</div>
                <div id="logoWrapper" class="hidden absolute -top-10"><img id="logoImg" src="" class="h-24 w-auto drop-shadow-lg bg-white rounded-full p-2"></div>
            <div class="mt-8 mb-6 w-20 h-1 bg-white/50 rounded-full"></div>
            <h1 id="cardTitle" class="text-[60px] font-bold text-white leading-tight font-['Hind_Siliguri'] drop-shadow-md mb-8">Title Here</h1>
            <div class="w-full border-t border-white/20 pt-6 flex justify-between items-center text-white/90 font-['Hind_Siliguri']">
                <span class="text-2xl font-bold tracking-widest uppercase" id="brandNameDisplay">Brand</span>
                <span class="text-2xl" id="currentDate">Date</span>
            </div>
        </div>
    </div>`;
</script>