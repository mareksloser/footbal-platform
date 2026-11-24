/**
 * @file
 * Club – mobilní menu, podmenu, lišta akcí a posuvné pásy.
 */
((Drupal, once) => {
  'use strict';

  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  Drupal.behaviors.clubMobileMenu = {
    attach(context) {
      once('club-menu', '[data-club-menu-toggle]', context).forEach((btn) => {
        const panel = document.getElementById(btn.getAttribute('aria-controls'));
        if (!panel) return;
        btn.addEventListener('click', () => {
          const open = panel.classList.toggle('hidden') === false;
          btn.setAttribute('aria-expanded', String(open));
        });
      });
    },
  };

  Drupal.behaviors.clubDropdowns = {
    attach(context) {
      once('club-dd', '.club-nav__toggle', context).forEach((btn) => {
        btn.addEventListener('click', (e) => {
          e.stopPropagation();
          const item = btn.closest('.club-nav__item');
          const open = !item.classList.contains('is-open');
          item.parentElement.querySelectorAll(':scope > .is-open').forEach((el) => {
            el.classList.remove('is-open');
            el.querySelector('.club-nav__toggle')?.setAttribute('aria-expanded', 'false');
          });
          item.classList.toggle('is-open', open);
          btn.setAttribute('aria-expanded', String(open));
        });
      });

      once('club-dd-close', 'html', context).forEach(() => {
        const closeAll = () => document.querySelectorAll('.club-nav__item.is-open').forEach((el) => {
          // V mobilním panelu nechat otevřené, zavírá se tlačítkem.
          if (el.closest('.club-mobile-nav')) return;
          el.classList.remove('is-open');
          el.querySelector('.club-nav__toggle')?.setAttribute('aria-expanded', 'false');
        });
        document.addEventListener('click', closeAll);
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeAll(); });
      });
    },
  };

  Drupal.behaviors.clubTicker = {
    attach(context) {
      once('club-ticker', '[data-club-ticker]', context).forEach((bar) => {
        const items = [...bar.querySelectorAll('[data-ticker-item]')];
        if (items.length < 2) return;
        const controls = bar.querySelector('[data-ticker-controls]');
        const count = bar.querySelector('[data-ticker-count]');
        let i = 0;
        const show = (n) => {
          i = (n + items.length) % items.length;
          items.forEach((el, k) => { el.hidden = k !== i; });
          if (count) count.textContent = `${i + 1}/${items.length}`;
        };
        controls.hidden = false;
        bar.querySelector('[data-ticker-prev]').addEventListener('click', () => { show(i - 1); restart(); });
        bar.querySelector('[data-ticker-next]').addEventListener('click', () => { show(i + 1); restart(); });
        let timer = null;
        const restart = () => {
          if (reduceMotion) return;
          clearInterval(timer);
          timer = setInterval(() => show(i + 1), 6000);
        };
        bar.addEventListener('mouseenter', () => clearInterval(timer));
        bar.addEventListener('mouseleave', restart);
        bar.addEventListener('focusin', () => clearInterval(timer));
        show(0);
        restart();
      });
    },
  };

  /**
   * Hlavička bloku – obalí nadpis bloku a vrátí kontejner pro akce vpravo.
   */
  const blockActions = (block) => {
    const title = block?.querySelector(':scope > .club-block__title, :scope > .club-block__head > .club-block__title');
    if (!title) return null;
    let head = title.parentElement.classList.contains('club-block__head') ? title.parentElement : null;
    if (!head) {
      head = document.createElement('div');
      head.className = 'club-block__head';
      title.before(head);
      head.append(title);
    }
    let actions = head.querySelector(':scope > .club-block__actions');
    if (!actions) {
      actions = document.createElement('div');
      actions.className = 'club-block__actions';
      head.append(actions);
    }
    return actions;
  };

  Drupal.behaviors.clubBlockHead = {
    attach(context) {
      once('club-head', '.club-block', context).forEach((block) => {
        // Jen vlastní odkaz bloku, ne odkazy vnořených bloků.
        const more = [...block.querySelectorAll('.more-link')].find((el) => el.closest('.club-block') === block);
        if (!more) return;
        const actions = blockActions(block);
        if (actions) actions.prepend(more);
      });
    },
  };

  Drupal.behaviors.clubCopy = {
    attach(context) {
      once('club-copy', '[data-club-copy]', context).forEach((btn) => {
        const label = btn.textContent;
        btn.addEventListener('click', async () => {
          try {
            await navigator.clipboard.writeText(btn.dataset.clubCopy);
            btn.textContent = Drupal.t('Zkopírováno');
          }
          catch (e) {
            window.prompt(Drupal.t('Zkopírujte odkaz:'), btn.dataset.clubCopy);
          }
          setTimeout(() => { btn.textContent = label; }, 2000);
        });
      });
    },
  };

  // Zvýraznění našeho řádku v tabulce soutěže (krátký název klubu z nastavení tématu).
  Drupal.behaviors.clubStandings = {
    attach(context) {
      once('club-standings', '[data-club-highlight]', context).forEach((box) => {
        const needle = box.dataset.clubHighlight.toLowerCase();
        box.querySelectorAll('tr').forEach((tr) => {
          if (tr.textContent.toLowerCase().includes(needle)) tr.classList.add('is-us');
        });
      });
    },
  };

  Drupal.behaviors.clubScroller = {
    attach(context) {
      once('club-scroller', '.club-scroller', context).forEach((view) => {
        const track = view.querySelector('.view-content');
        if (!track) return;
        const nav = document.createElement('div');
        nav.className = 'club-scroller__nav';
        const icon = (d) => `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="${d}"/></svg>`;
        nav.innerHTML = `<button type="button" data-dir="-1" aria-label="${Drupal.t('Posunout doleva')}">${icon('M15 18l-6-6 6-6')}</button>`
          + `<button type="button" data-dir="1" aria-label="${Drupal.t('Posunout doprava')}">${icon('M9 18l6-6-6-6')}</button>`;
        nav.addEventListener('click', (e) => {
          const btn = e.target.closest('button');
          if (!btn) return;
          track.scrollBy({ left: Number(btn.dataset.dir) * track.clientWidth * 0.8, behavior: reduceMotion ? 'auto' : 'smooth' });
        });
        const actions = blockActions(view.closest('.club-block'));
        if (actions) {
          actions.append(nav);
        }
        else {
          track.before(nav);
        }
        const update = () => { nav.hidden = track.scrollWidth <= track.clientWidth + 4; };
        update();
        new ResizeObserver(update).observe(track);
      });
    },
  };
})(Drupal, once);
