import app from 'flarum/admin/app';
import Extend from 'flarum/common/extenders';
import RecipientGroupsSetting from './components/RecipientGroupsSetting';

export default [
  new Extend.Admin()
    // `customSetting` callbacks run in the settings page's context, which is
    // what gives us `this.setting(...)` for the bidirectional stream.
    .customSetting(function () {
      return <RecipientGroupsSetting stream={this.setting('ekumanov-spam-alerts.group_ids', '["1"]')} />;
    }, 20)
    .setting(
      () => ({
        setting: 'ekumanov-spam-alerts.usernames',
        label: app.translator.trans('ekumanov-spam-alerts.admin.settings.usernames_label'),
        help: app.translator.trans('ekumanov-spam-alerts.admin.settings.usernames_help'),
        type: 'text',
        placeholder: 'alice, bob',
      }),
      10
    ),
];
