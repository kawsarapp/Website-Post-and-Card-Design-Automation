<script>
templates.broadcast_tv = `
    <div class="relative h-full w-full bg-gray-900 overflow-hidden">
        <div class="h-[85%] w-full relative">
            <img id="cardImage" src="" class="absolute inset-0 w-full h-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-b from-transparent via-transparent to-black/80"></div>
                <div class="absolute top-8 left-8 flex items-center gap-3">
                    <div class="bg-red-600 text-white px-4 py-1 text-xl font-bold uppercase animate-pulse">● LIVE</div>
                    <div id="textBadge" class="bg-black/50 text-white px-4 py-1 text-xl font-bold uppercase backdrop-blur-sm">UPDATE</div>
                    <div id="logoWrapper" class="hidden"><img id="logoImg" src="" class="h-16 w-auto"></div>
            </div>
        </div>
        <div class="h-[15%] w-full bg-blue-900 relative flex items-center px-10 border-t-4 border-yellow-400">
                <div class="bg-yellow-400 text-blue-900 font-black text-3xl px-6 py-2 absolute -top-8 left-10 skew-x-[-15deg] shadow-lg">
                LATEST NEWS
                </div>
                <div class="w-full flex justify-between items-center text-white pt-2">
                <h1 id="cardTitle" class="text-[40px] font-bold font-['Hind_Siliguri'] line-clamp-1 w-[80%] leading-normal">Headline goes here...</h1>
                <div class="flex flex-col items-end border-l pl-6 border-blue-700">
                        <span class="text-xl font-bold text-yellow-400 uppercase" id="brandNameDisplay">Brand</span>
                        <span class="text-lg opacity-80" id="currentDate">Date</span>
                </div>
                </div>
        </div>
    </div>`;
</script>