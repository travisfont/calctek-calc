<template>
  <div class="bg-[#0a0a0a] text-gray-100 min-h-screen p-4 font-sans flex items-center justify-center">
    <!-- BEGIN: MainCalculatorContainer -->
    <main
      class="w-full bg-[#1a1a1a] rounded-[2rem] shadow-2xl overflow-hidden flex flex-col md:flex-row border border-[#262626] max-w-6xl"
      data-purpose="calculator-wrapper">
      <!-- BEGIN: CalculatorInterface -->
      <section class="flex flex-col gap-6 w-full md:w-[450px] flex-shrink-0 pb-6" data-purpose="calculator-keypad-section">
        <!-- Display Area -->
        <div class="p-6 text-right flex flex-col justify-end min-h-[140px] bg-[#121212] border border-[#333] rounded-2xl mx-6 mt-6 mb-2 shadow-inner group transition-all duration-300 ring-1 ring-white/5">
          <div class="text-gray-500 text-sm h-6 overflow-hidden mb-1" id="prev-operation">{{ previousOperation }}</div>
          <input type="text" class="font-medium tracking-tight text-4xl font-semibold bg-transparent border-none outline-none text-right w-full p-0 focus:ring-0" id="display" v-model="currentInput" @keyup.enter="calculate" @focus="handleFocus" @blur="handleBlur" />
        </div>
        <!-- Buttons Grid -->
        <div class="grid grid-cols-4 gap-3 pt-0 px-6" data-purpose="keypad-grid">
          <!-- Row 1 -->
          <button
            class="bg-[#333333] hover:bg-[#404040] text-gray-200 py-5 rounded-xl transition-all text-xl font-medium col-span-2"
            @click="clearDisplay">AC</button>
          <button class="bg-[#333333] hover:bg-[#404040] text-gray-200 py-5 rounded-xl transition-all text-xl font-medium"
            @click="deleteLast">
            <svg class="h-6 w-6 mx-auto" fill="none" stroke="currentColor" viewbox="0 0 24 24"
              xmlns="http://www.w3.org/2000/svg">
              <path
                d="M12 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M3 12l6.414 6.414a2 2 0 001.414.586H19a2 2 0 002-2V7a2 2 0 00-2-2h-8.172a2 2 0 00-1.414.586L3 12z"
                stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
            </svg>
          </button>
          <button class="bg-[#1e3a5f] hover:bg-[#254a7a] text-[#38bdf8] py-5 rounded-xl transition-all text-2xl font-bold"
            @click="appendOperator('/')">÷</button>
          <!-- Row 2 -->
          <button class="bg-[#262626] hover:bg-[#333333] text-white py-5 rounded-xl transition-all text-xl font-medium"
            @click="appendNumber('7')">7</button>
          <button class="bg-[#262626] hover:bg-[#333333] text-white py-5 rounded-xl transition-all text-xl font-medium"
            @click="appendNumber('8')">8</button>
          <button class="bg-[#262626] hover:bg-[#333333] text-white py-5 rounded-xl transition-all text-xl font-medium"
            @click="appendNumber('9')">9</button>
          <button class="bg-[#1e3a5f] hover:bg-[#254a7a] text-[#38bdf8] py-5 rounded-xl transition-all text-2xl font-bold"
            @click="appendOperator('*')">×</button>
          <!-- Row 3 -->
          <button class="bg-[#262626] hover:bg-[#333333] text-white py-5 rounded-xl transition-all text-xl font-medium"
            @click="appendNumber('4')">4</button>
          <button class="bg-[#262626] hover:bg-[#333333] text-white py-5 rounded-xl transition-all text-xl font-medium"
            @click="appendNumber('5')">5</button>
          <button class="bg-[#262626] hover:bg-[#333333] text-white py-5 rounded-xl transition-all text-xl font-medium"
            @click="appendNumber('6')">6</button>
          <button class="bg-[#1e3a5f] hover:bg-[#254a7a] text-[#38bdf8] py-5 rounded-xl transition-all text-2xl font-bold"
            @click="appendOperator('-')">-</button>
          <!-- Row 4 -->
          <button class="bg-[#262626] hover:bg-[#333333] text-white py-5 rounded-xl transition-all text-xl font-medium"
            @click="appendNumber('1')">1</button>
          <button class="bg-[#262626] hover:bg-[#333333] text-white py-5 rounded-xl transition-all text-xl font-medium"
            @click="appendNumber('2')">2</button>
          <button class="bg-[#262626] hover:bg-[#333333] text-white py-5 rounded-xl transition-all text-xl font-medium"
            @click="appendNumber('3')">3</button>
          <button class="bg-[#1e3a5f] hover:bg-[#254a7a] text-[#38bdf8] py-5 rounded-xl transition-all text-2xl font-bold"
            @click="appendOperator('+')">+</button>
          <!-- Row 5 -->
          <button
            class="bg-[#262626] hover:bg-[#333333] text-white py-5 rounded-xl transition-all text-xl font-medium"
            @click="appendNumber('0')">0</button>
          <button class="bg-[#262626] hover:bg-[#333333] text-white py-5 rounded-xl transition-all text-xl font-medium"
            @click="toggleSign">+/-</button>
          <button class="bg-[#262626] hover:bg-[#333333] text-white py-5 rounded-xl transition-all text-xl font-medium"
            @click="appendNumber('.')">.</button>
          <button
            class="text-white py-5 rounded-xl transition-all text-2xl font-bold bg-[#10b981] hover:bg-[#059669] shadow-[0_0_15px_rgba(16,185,129,0.4)]"
            @click="calculate">=</button>
        </div>
      </section>
      <!-- END: CalculatorInterface -->
      <!-- BEGIN: TickerTapeHistory -->
      <aside class="w-full bg-[#141414] p-8 border-t md:border-t-0 md:border-l border-[#262626] flex flex-col flex-1"
        data-purpose="history-sidebar">
        <div class="mb-6 flex items-center justify-center">
          <h2 class="font-bold uppercase tracking-widest text-gray-500 text-lg">Calculator History</h2>
        </div>
        <div class="ticker-tape ticker-scroll flex-1 overflow-y-auto space-y-4 pr-2 text-sm text-gray-400"
          id="history-list" ref="historyListRef">
          <div v-if="history.length === 0" class="italic text-gray-600 opacity-50 text-center py-10 uppercase tracking-tighter text-[10px]">
            Tape is empty
          </div>
          <div v-else v-for="item in history" :key="item.id" 
            class="border-b border-gray-800 pb-2" 
            :class="{ 'animate-pulse': item.isNew }">
            <div class="text-[10px] text-gray-500 mb-1">{{ item.time }}</div>
            <div class="flex justify-between items-center">
              <span class="truncate mr-2">{{ item.expression }}</span>
              <span class="text-emerald-400 font-bold">= {{ item.result }}</span>
            </div>
          </div>
        </div>
      </aside>
      <!-- END: TickerTapeHistory -->
    </main>
    <!-- END: MainCalculatorContainer -->
  </div>
</template>

<script setup src="./Calculator.js"></script>

<style scoped>
/* Custom scrollbar for the ticker tape history */
.ticker-scroll::-webkit-scrollbar {
  width: 4px;
}

.ticker-scroll::-webkit-scrollbar-track {
  background: #1f2937;
}

.ticker-scroll::-webkit-scrollbar-thumb {
  background: #4b5563;
  border-radius: 10px;
}

/* Monospace style for ticker tape as requested */
.ticker-tape {
  font-family: 'ui-monospace', SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
}
</style>
