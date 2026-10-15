import app from 'flarum/admin/app';
import Component from 'flarum/common/Component';
import Switch from 'flarum/common/components/Switch';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';

/**
 * Checklist of groups whose members are emailed. Stored as a JSON array of
 * group IDs, so renaming a group never silently changes who is emailed.
 */
export default class RecipientGroupsSetting extends Component {
  oninit(vnode) {
    super.oninit(vnode);

    this.loading = false;

    // Groups are not part of the admin payload, so they have to be fetched.
    if (!app.store.all('groups').length) {
      this.loading = true;

      app.store
        .find('groups')
        .catch(() => {})
        .then(() => {
          this.loading = false;
          m.redraw();
        });
    }
  }

  view() {
    return (
      <div className="Form-group">
        <label>{app.translator.trans('ekumanov-spam-alerts.admin.settings.groups_label')}</label>
        <div className="helpText">{app.translator.trans('ekumanov-spam-alerts.admin.settings.groups_help')}</div>
        {this.loading ? (
          <LoadingIndicator display="inline" />
        ) : (
          app.store
            .all('groups')
            // Guests and Members are everyone, which is never what this is for.
            .filter((group) => !['2', '3'].includes(group.id()))
            .sort((a, b) => a.namePlural().localeCompare(b.namePlural()))
            .map((group) => (
              <Switch state={this.selected().includes(group.id())} onchange={(checked) => this.toggle(group.id(), checked)}>
                {group.namePlural()}
              </Switch>
            ))
        )}
      </div>
    );
  }

  selected() {
    try {
      const ids = JSON.parse(this.attrs.stream() || '[]');

      return Array.isArray(ids) ? ids.map(String) : [];
    } catch (e) {
      return [];
    }
  }

  toggle(id, checked) {
    const ids = this.selected().filter((other) => other !== id);

    if (checked) ids.push(id);

    this.attrs.stream(JSON.stringify(ids.sort((a, b) => a - b)));
  }
}
