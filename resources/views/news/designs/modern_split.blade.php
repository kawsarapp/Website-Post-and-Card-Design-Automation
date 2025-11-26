<script>
templates.modern_split = `
    <div class="flex h-full w-full bg-white relative">
        <div class="w-1/2 h-full relative overflow-hidden">
            <img id="cardImage" src="" class="absolute inset-0 w-full h-full object-cover">
            <div class="absolute inset-0 bg-black/10"></div>
            <div class="absolute top-8 left-8">
                    <div id="textBadge" class="bg-blue-600 text-white px-6 py-2 text-2xl font-bold uppercase tracking-widest shadow-md">NEWS</div>
                    <div id="logoWrapper" class="hidden"><img id="logoImg" src="" class="h-20 w-auto"></div>
            </div>
        </div>
        <div class="w-1/2 h-full p-16 flex flex-col justify-center bg-slate-50 relative">
            <div class="w-20 h-2 bg-blue-600 mb-10"></div>
            <div id="textWrapper" class="w-full">
                <h1 id="cardTitle" class="text-[55px] font-extrabold text-slate-900 leading-[1.2] font-['Hind_Siliguri'] mb-8 text-left">Title Here</h1>
            </div>
            <div class="mt-auto border-t-2 border-slate-200 pt-8 flex justify-between items-center text-slate-500 font-['Hind_Siliguri']">
                    <span class="text-2xl font-bold uppercase text-blue-900 tracking-wider" id="brandNameDisplay">Brand</span>
                    <span class="text-2xl font-medium" id="currentDate">Date</span>
            </div>
        </div>
    </div>`;
</script>