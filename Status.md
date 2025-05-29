## ContentManager Refactoring - Phase 1 - 2025-05-29

This phase focused on initial structural improvements to `Class/ContentManager.php` for better maintainability.

### Changes Implemented:

1.  **Extracted Schedule Logic to `ScheduleManager`**:
    *   Created a new class `Glory\Helper\ScheduleManager` in `Helper/ScheduleManager.php`.
    *   Moved the `schedule` method from `ContentManager` to `ScheduleManager::getScheduleData`.
    *   Moved the `getCurrentStatus` method from `ContentManager` to `ScheduleManager::getCurrentScheduleStatus`.
    *   `ContentManager` now delegates calls to these methods to `ScheduleManager`.
    *   This separation of concerns makes `ContentManager` less cluttered and centralizes schedule-specific logic.

2.  **Internal Refactoring of Synchronization Logic**:
    *   Within `ContentManager.php`, the complex logic for synchronizing option values between code defaults and database values (previously a large part of the `register` method) was extracted.
    *   This logic is now encapsulated in a new private static method `_synchronizeRegisteredOption(string $key)`.
    *   The `register` method is now shorter and its primary responsibilities are clearer.

### To Do / Items to Test:

*   **Schedule Functionality**:
    *   Thoroughly test any pages or features that display or rely on schedule information (e.g., opening hours status).
    *   Verify that schedules are correctly fetched and parsed via `ContentManager::schedule()` (now `ScheduleManager::getScheduleData()`).
    *   Verify that `ContentManager::getCurrentStatus()` (now `ScheduleManager::getCurrentScheduleStatus()`) correctly reports open/closed states based on the defined schedules and current time.
*   **Content Registration & Retrieval**:
    *   Test the registration of new content fields using `ContentManager::register()`.
    *   Test the retrieval of content using `ContentManager::get()`, `ContentManager::text()`, `ContentManager::richText()`, `ContentManager::image()`.
    *   Verify that the default value handling (code vs. panel-saved) works as expected after the synchronization logic refactoring. This includes:
        *   Initial registration (should use code default).
        *   Saving a value from a panel (should override code default if applicable).
        *   Changing a code default for a panel-saved value (should respect panel value if hashes match, or update if hashes mismatch, as per original logic).
        *   `force_default_on_register` behavior.
*   **Logging**:
    *   Monitor PHP error logs and any specific `GloryLogger` outputs for new errors or warnings related to `ContentManager` or `ScheduleManager`.
*   **Dependencies**:
    *   Confirm that `GloryLogger` static calls continue to function correctly from within `ScheduleManager`. (Currently assumed to be fine as they are static calls to a namespaced class).
*   **Further Refactoring Considerations (for future phases)**:
    *   Evaluate if `ContentManager` itself could be broken down further (e.g., separating content registration from value retrieval).
    *   Assess if the `registerOnTheFly` logic can be simplified or made more robust.
    *   Consider if `ScheduleManager` having a dependency on `ContentManager::get()` is optimal in the long term, or if schedule data should be fetched more directly.
