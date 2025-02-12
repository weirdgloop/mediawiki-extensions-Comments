/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./src/frontend/AddCommentController.js":
/*!**********************************************!*\
  !*** ./src/frontend/AddCommentController.js ***!
  \**********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
class AddCommentController {
  constructor() {
    this.$container = $('<div>').attr('id', 'ext-comments-add-comment');
    this.$input = $('<textarea>').attr('id', 'ext-comments-add-comment-input');
  }
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (AddCommentController);

/***/ }),

/***/ "./src/frontend/util.js":
/*!******************************!*\
  !*** ./src/frontend/util.js ***!
  \******************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   isElementInView: () => (/* binding */ isElementInView)
/* harmony export */ });
/**
 * @param {HTMLElement} el
 * @returns {boolean}
 */
const isElementInView = el => {
  const rect = el.getBoundingClientRect();
  return rect.top >= 0 && rect.left >= 0 && rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) && rect.right <= (window.innerWidth || document.documentElement.clientWidth);
};

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be isolated against other modules in the chunk.
(() => {
/*!*******************************!*\
  !*** ./src/frontend/index.js ***!
  \*******************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _util__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./util */ "./src/frontend/util.js");
/* harmony import */ var _AddCommentController__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./AddCommentController */ "./src/frontend/AddCommentController.js");




class Comments {
  constructor() {
    this.init = false;
    this.addCommentController = new _AddCommentController__WEBPACK_IMPORTED_MODULE_1__["default"]();
    this.$commentTree = $('<div>').attr('id', 'ext-comments-tree');
    this.$container = $('<div>').attr('id', 'ext-comments-container').append($('<h3>').text(mw.message('comments-container-header').text()), this.addCommentController.$container, this.$commentTree);
    this.addEventListeners();
  }

  /**
   * Add the container for the comments interface to the page
   */
  addContainerToPage() {
    $('#bodyContent').append(this.$container);
  }

  /**
   * Add the required event listeners
   */
  addEventListeners() {
    $(() => this.addContainerToPage());
    $(window).on('DOMContentLoaded load resize scroll', () => {
      if ((0,_util__WEBPACK_IMPORTED_MODULE_0__.isElementInView)(this.$container) && !this.init) {
        this.init = true;
        // TODO actually make initial API calls and render things
      }
    });
  }
}
const comments = new Comments();
})();

/******/ })()
;
//# sourceMappingURL=index.js.map.json