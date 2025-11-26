<script>
templates.neon_dark = `
    <div class="h-full w-full bg-[#0a0a0a] relative flex flex-col overflow-hidden">
        <div class="absolute top-[-20%] right-[-20%] w-[800px] h-[800px] bg-purple-900/30 rounded-full blur-[120px]"></div>
        <div class="absolute bottom-[-20%] left-[-20%] w-[600px] h-[600px] bg-blue-900/20 rounded-full blur-[100px]"></div>
        <div class="h-[55%] w-full relative z-10 p-8 pb-0">
            <div class="w-full h-full rounded-2xl overflow-hidden border border-gray-800 relative group">
                <img id="cardImage" src="" class="w-full h-full object-cover opacity-80 group-hover:opacity-100 transition duration-500">
                <div class="absolute bottom-0 left-0 w-full h-1/2 bg-gradient-to-t from-[#0a0a0a] to-transparent"></div>
                    <div class="absolute top-6 right-6">
                    <span class="text-cyan-400 border border-cyan-400 px-4 py-1 text-xl font-mono tracking-widest uppercase rounded bg-cyan-900/20" id="textBadge">TECH</span>
                    <div id="logoWrapper" class="hidden"><img id="logoImg" src="" class="h-16 w-auto brightness-200 contrast-125"></div>
                </div>
            </div>
        </div>
        <div class="h-[45%] w-full p-10 z-10 flex flex-col justify-center">
            <h1 id="cardTitle" class="text-[55px] font-bold text-transparent bg-clip-text bg-gradient-to-r from-white to-gray-400 font-['Hind_Siliguri'] leading-tight mb-6">Title Here</h1>
            <div class="flex items-center justify-between border-t border-gray-800 pt-6 mt-auto">
                    <span class="text-2xl font-bold text-purple-500 uppercase tracking-widest" id="brandNameDisplay">Brand</span>
                    <span class="text-xl text-gray-500 font-mono" id="currentDate">Date</span>
            </div>
        </div>
    </div>`;
</script>