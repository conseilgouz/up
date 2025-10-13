/* 
 * UP - init shuffle (from https://codepen.io/Vestride/pen/ZVWmMX )
 */
var Shuffle = window.Shuffle;
class UpShuffle {
  constructor(element) {
    this.element = element;
    this.shuffle = new Shuffle(element, {
      itemSelector: '.picture-item',
      sizer: element.querySelector('.shuffle-grid-sizer'),
   	  delimiter: ',',
    });
    // handle events.
    this.addFilterButtons();
    this.addSearchFilter();
	if (document.querySelector('.filter-options').getAttribute('data-random')) { // random display required
		this.shuffle.sort({randomize:true}); // random order
	}
  }

  addFilterButtons() {
    const options = document.querySelector('.filter-options');
    if (!options) {
      return;
    }
    const filterButtons = Array.from(options.children);
    const onClick = this._handleFilterClick.bind(this);
    filterButtons.forEach((button) => {
      button.addEventListener('click', onClick, false);
    });
  }

  _handleFilterClick(evt) {
    const btn = evt.currentTarget;
    const isActive = btn.classList.contains('active');
    const btnGroup = btn.getAttribute('data-group');
    this._removeActiveClassFromChildren(btn.parentNode);
   	if (document.querySelector('.js-shuffle-search') )
		document.querySelector('.js-shuffle-search').value = "";
    let filterGroup;
    if (isActive) {
      btn.classList.remove('active');
      filterGroup = Shuffle.ALL_ITEMS;
	  btn.parentNode.children[0].classList.add('active'); // ALL 
    } else {
      btn.classList.add('active');
      filterGroup = btnGroup;
    }
    if (filterGroup == "all") { // All
		this.shuffle.filter();
	} else { // un filtre 
		this.shuffle.filter('['+filterGroup+']');
	}
  }
  _removeActiveClassFromChildren(parent) {
    const { children } = parent;
    for (let i = children.length - 1; i >= 0; i--) {
      children[i].classList.remove('active');
    }
  }
  // Advanced filtering
  addSearchFilter() {
    const searchInput = document.querySelector('.js-shuffle-search');
    if (!searchInput) {
      return;
    }
    searchInput.addEventListener('keyup', this._handleSearchKeyup.bind(this));
    searchInput.addEventListener('search', this._handleSearchKeyup.bind(this)); // bouton x
  }

  /**
   * Filter the shuffle instance by items with a title that matches the search input.
   * @param {Event} evt Event object.
   */
  _handleSearchKeyup(evt) {
    const searchText = evt.target.value.toLowerCase();
	this._removeActiveClassFromChildren(document.querySelector('.filter-options'));
	this.shuffle.filter();
    this.shuffle.filter((element, shuffle) => {
      // If there is a current filter applied, ignore elements that don't match it.
      if (shuffle.group !== Shuffle.ALL_ITEMS) {
        // Get the item's groups.
        const groups = JSON.parse(element.getAttribute('data-groups'));
        const isElementInCurrentGroup = groups.indexOf(shuffle.group) !== -1;
        // Only search elements in the current group
        if (!isElementInCurrentGroup) {
          return false;
        }
      }
      const imgElement = element.querySelector('.picture-item img');
      const alt = imgElement['alt'].toLowerCase().trim();
      const title = imgElement.getAttribute('data-name').toLowerCase().trim();
      return (alt.indexOf(searchText) !== -1) || (title.indexOf(searchText) !== -1);
    });
  }
}

document.addEventListener('DOMContentLoaded', () => {
  window.upshuffle = new UpShuffle(document.querySelector('.shuffle-grid'));
});
