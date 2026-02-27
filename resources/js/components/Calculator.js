import { ref, nextTick } from 'vue';
import { evaluate } from 'mathjs';

const currentInput = ref('0');         // current input
const previousOperation = ref('');     // previous operation
const shouldResetDisplay = ref(false); // reset display
const history = ref([]);               // history
const historyListRef = ref(null);      // history list

// append number to current input
// if current input is 0, replace it with the number
// if current input is not 0, append the number
// if shouldResetDisplay is true, replace the current input with the number
const appendNumber = (num) => {
    if (currentInput.value === '0' || shouldResetDisplay.value) {
        currentInput.value = num;
        shouldResetDisplay.value = false;
    } else {
        if (num === '.' && currentInput.value.includes('.')) return;
        currentInput.value += num;
    }
};

// handle focus on input
// if current input is 0, replace it with empty string
const handleFocus = () => {
    if (currentInput.value === '0') {
        currentInput.value = '';
    }
};

// handle blur on input
// if current input is empty, replace it with 0
const handleBlur = () => {
    if (currentInput.value.trim() === '') {
        currentInput.value = '0';
    }
};

// append operator to current input
// if shouldResetDisplay is true, replace the current input with the operator
// if the last character is an operator, replace it with the new operator
// if the last character is not an operator, append the operator
const appendOperator = (op) => {
    if (shouldResetDisplay.value) {
        shouldResetDisplay.value = false;
    }

    // if the last character is an operator, replace it with the new operator
    const lastChar = currentInput.value.slice(-1);

    // if the last character is an operator, replace it with the new operator
    if (['+', '-', '*', '/'].includes(lastChar)) {
        currentInput.value = currentInput.value.slice(0, -1) + op;
    } else {
        currentInput.value += op;
    }
};

// delete last character from current input
// if current input has more than 1 character, remove the last character
// if current input has 1 character, replace it with 0
const deleteLast = () => {
    if (currentInput.value.length > 1) {
        currentInput.value = currentInput.value.slice(0, -1);
    } else {
        currentInput.value = '0';
    }
};

// clear display
// reset current input to 0
// reset previous operation to empty string
// reset shouldResetDisplay to false
const clearDisplay = () => {
    currentInput.value = '0';
    previousOperation.value = '';
    shouldResetDisplay.value = false;
};

// calculate the result of the current input
// if the current input is 0, do nothing
// if the current input is not 0, evaluate the expression
// if the expression is valid, add it to the history
// if the expression is invalid, set the current input to "Error"
// if the current input is "Error", clear it after 1.5 seconds
const calculate = () => {
    try {
        const expression = currentInput.value;

        // Evaluate the expression using mathjs
        let result = evaluate(expression);

        // Format to avoid long decimal trails if necessary, but keep precision high
        const formattedResult = Number.isInteger(result) ? result : parseFloat(Number(result).toFixed(8));

        addToHistory(expression, formattedResult);

        previousOperation.value = expression + ' =';
        currentInput.value = formattedResult.toString();
        shouldResetDisplay.value = true;
    } catch (e) {
        currentInput.value = 'Error';
        setTimeout(clearDisplay, 1500);
    }
};

// add expression and result to history
// if the history is empty, add the new item to the history
// if the history is not empty, add the new item to the history
// if the history has more than 10 items, remove the oldest item
const addToHistory = (expr, res) => {
    const newItem = {
        id: Date.now(),
        time: new Date().toLocaleTimeString(),
        expression: expr,
        result: res,
        isNew: true
    };

    // add new item to history
    history.value.unshift(newItem);

    // scroll to top of history
    nextTick(() => {
        if (historyListRef.value) {
            historyListRef.value.scrollTop = 0;
        }
    });

    // remove new item from history after 1 second
    setTimeout(() => {
        const index = history.value.findIndex(item => item.id === newItem.id);
        if (index !== -1) {
            history.value[index].isNew = false;
        }
    }, 1000);
};

// toggle sign of current input
// if current input is 0 or "Error", do nothing
// if current input starts with "-", remove it
// if current input does not start with "-", add it
const toggleSign = () => {
    if (currentInput.value === '0' || currentInput.value === 'Error') return;

    if (currentInput.value.startsWith('-')) {
        currentInput.value = currentInput.value.substring(1);
    } else {
        currentInput.value = '-' + currentInput.value;
    }
};
