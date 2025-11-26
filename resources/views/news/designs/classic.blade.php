<script>
templates.classic = `
    <div id="imagePart" class="h-[60%] relative overflow-hidden group bg-gray-100 transition-all duration-500 w-full">
        <img id="cardImage" src="" class="absolute inset-0 w-full h-full object-cover">
        <div id="overlayGradient" class="hidden absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent z-0"></div>
        <div id="badgeContainer" class="absolute top-10 left-10 z-10">
            <div id="textBadge" class="bg-red-600 text-white px-8 py-3 text-4xl font-bold uppercase tracking-widest shadow-lg rounded-md">NEWS</div>
            <div id="logoWrapper" class="hidden filter drop-shadow-2xl"><img id="logoImg" src="" class="h-28 w-auto object-contain"></div>
        </div>
    </div>
    <div id="cardBody" class="h-[40%] px-12 pt-5 pb-6 flex flex-col justify-center items-center text-center relative bg-white text-black transition-all duration-500 w-full">
        <div id="textWrapper" class="flex-1 w-full flex flex-col items-center justify-center">
            <div id="decoLine" class="w-24 h-3 bg-red-600 mb-4 rounded-full transition-colors duration-300"></div>
            <h1 id="cardTitle" class="text-[50px] font-bold leading-snug font-['Hind_Siliguri'] line-clamp-4 pb-1 w-full drop-shadow-none transition-all duration-200">Title Here</h1>
        </div>
        <div id="cardFooter" class="w-full mt-auto pt-4 border-t border-current border-opacity-20 flex justify-between items-end opacity-80 font-['Hind_Siliguri']">
            <span class="text-3xl font-bold tracking-wide uppercase" id="brandNameDisplay">Brand</span>
            <span class="text-3xl font-bold" id="currentDate">Date</span>
        </div>
    </div>`;
</script>