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
  constructor(restApi, config, commentListController) {
    const self = this;

    /** @type MediaWiki.Rest */
    this.restApi = restApi;

    /** @type object */
    this.config = config;
    this.commentListController = commentListController;
    this.parentId = null;

    // Create all the DOM elements
    this.$container = $('<div>').attr('id', 'ext-comments-add-comment');
    this.$inputArea = $('<div>').addClass('ve-area-wrapper');
    this.$input = $('<textarea>').attr({
      rows: 5,
      placeholder: mw.msg('comments-add-comment-placeholder')
    });
    this.submitBtn = new OO.ui.ButtonWidget({
      label: 'Post comment'
    });
    this.submitBtn.on('click', () => {
      self.postComment();
    });
    this.$toolbar = $('<div>').attr('id', 'ext-comments-add-comment-toolbar').append(this.submitBtn.$element);

    // Add the elements to the DOM
    this.$inputArea.append(this.$input);
    this.$container.append(this.$inputArea, this.$toolbar);

    // Apply VE using VEForAll
    this.$input.applyVisualEditor();
  }
  getCurrentVe() {
    const ins = this.$input.getVEInstances();
    return ins[ins.length - 1];
  }
  postComment() {
    const self = this;
    const target = self.getCurrentVe().target;
    const document = target.getSurface().getModel().getDocument();
    target.getWikitextFragment(document).then(wikitext => {
      this.restApi.post('/comments/v0/comment', {
        pageid: self.config.wgArticleId,
        parentid: self.parentId,
        text: wikitext
      });
    });
  }
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (AddCommentController);

/***/ }),

/***/ "./src/frontend/Comment.js":
/*!*********************************!*\
  !*** ./src/frontend/Comment.js ***!
  \*********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
class Comment {
  constructor(data) {
    this.id = data.id || null;
    this.deleted = data.deleted !== null ? data.deleted : false;
    this.rating = data.rating || 0;
    this.html = data.html || '';
    this.wikitext = data.wikitext || '';
    this.actor = data.actor || {};
    this.isEditing = false;
    this.$element = $('<div>').addClass('ext-comments-comment-wrapper').data('id', this.id).html(this.html);
  }
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Comment);

/***/ }),

/***/ "./src/frontend/CommentListContoller.js":
/*!**********************************************!*\
  !*** ./src/frontend/CommentListContoller.js ***!
  \**********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _util__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./util */ "./src/frontend/util.js");
/* harmony import */ var _Comment__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Comment */ "./src/frontend/Comment.js");


class CommentListContoller {
  constructor(restApi, config) {
    this.init = false;
    /** @type MediaWiki.Rest */
    this.restApi = restApi;
    /** @type object */
    this.config = config;
    this.$container = $('<div>').attr('id', 'ext-comments-tree');
  }
  loadComments() {
    const self = this;
    this.restApi.get('/comments/v0/page/' + self.config.wgArticleId, {}).then(res => {
      if (Object.prototype.hasOwnProperty.call(res, ['comments'])) {
        for (const data of res.comments) {
          this.comments.push(new _Comment__WEBPACK_IMPORTED_MODULE_1__["default"](data));
        }
      }
    });
  }
  addEventListeners() {
    const self = this;
    $(window).on('DOMContentLoaded load resize scroll', () => {
      if ((0,_util__WEBPACK_IMPORTED_MODULE_0__.isElementInView)(this.$container) && !this.init) {
        this.init = true;
        self.loadComments();
      }
    });
  }
}
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (CommentListContoller);

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
 * @param {HTMLElement|jQuery} el
 * @returns {boolean}
 */
const isElementInView = el => {
  if (el instanceof jQuery) {
    el = el[0];
  }
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
/* harmony import */ var _AddCommentController__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./AddCommentController */ "./src/frontend/AddCommentController.js");
/* harmony import */ var _CommentListContoller__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./CommentListContoller */ "./src/frontend/CommentListContoller.js");




class Comments {
  constructor() {
    this.init = false;
    this.config = mw.config.get(['wgArticleId']);
    this.restApi = new mw.Rest();
    this.commentListController = new _CommentListContoller__WEBPACK_IMPORTED_MODULE_1__["default"](this.restApi, this.config);
    this.addCommentController = new _AddCommentController__WEBPACK_IMPORTED_MODULE_0__["default"](this.restApi, this.config, this.commentListController);
    this.$container = $('<div>').attr('id', 'ext-comments-container').append($('<h3>').text(mw.message('comments-container-header').text()), this.addCommentController.$container, this.commentListController.$container);
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
    this.commentListController.addEventListeners();
  }
}
window.comments = new Comments();
})();

/******/ })()
;
//# sourceMappingURL=index.js.map.json