<div class="flex-1 bg-gray-200 flex items-center justify-center overflow-auto relative p-10 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')]">
    <div class="absolute bottom-6 right-6 flex bg-white shadow-lg rounded-full px-4 py-2 gap-4 z-40">
       <button onclick="changeZoom(-0.1)" class="font-bold text-gray-600 hover:text-indigo-600 text-xl">-</button>
       <span class="text-sm font-bold text-gray-400 pt-1">ZOOM</span>
       <button onclick="changeZoom(0.1)" class="font-bold text-gray-600 hover:text-indigo-600 text-xl">+</button>
   </div>

   <div id="preview-wrapper" class="shadow-2xl transition-transform duration-200 ease-out origin-center ring-8 ring-white">
       <div id="canvas-container" class="bg-white relative flex flex-col overflow-hidden"
               style="width: 1080px; height: 1080px; flex-shrink: 0;">
       </div>
   </div>
</div>