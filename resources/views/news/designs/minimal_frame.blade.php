<script>
templates.minimal_frame = `
    <div class="h-full w-full bg-[#f0f2f5] p-12 flex flex-col justify-center items-center">
        <div class="bg-white p-6 rounded-3xl shadow-[0_20px_60px_-15px_rgba(0,0,0,0.15)] w-full h-full flex flex-col">
            <div class="h-[65%] w-full rounded-2xl overflow-hidden relative">
                    <img id="cardImage" src="" class="w-full h-full object-cover transform hover:scale-105 transition duration-700">
                    <div class="absolute top-6 left-6">
                    <span class="bg-white/90 backdrop-blur text-black px-5 py-2 rounded-lg text-xl font-bold shadow-sm border border-gray-100 uppercase" id="textBadge">News</span>
                    <div id="logoWrapper" class="hidden"><img id="logoImg" src="" class="h-14 w-auto"></div>
                    </div>
            </div>
            <div class="h-[35%] flex flex-col justify-center px-4 pt-6">
                <h1 id="cardTitle" class="text-[50px] font-bold text-gray-800 leading-tight font-['Hind_Siliguri'] line-clamp-3">Title Here</h1>
                <div class="mt-auto flex items-center gap-4 border-t border-gray-100 pt-6">
                    <div class="h-10 w-10 rounded-full bg-indigo-600 flex items-center justify-center text-white font-bold text-xl">N</div>
                    <div class="flex flex-col">
                        <span class="text-lg font-bold text-gray-700 leading-none" id="brandNameDisplay">Brand Name</span>
                        <span class="text-base text-gray-400" id="currentDate">Date</span>
                    </div>
                </div>
            </div>
        </div>
    </div>`;
</script>